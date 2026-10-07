<?php

namespace App\Modules\Booking\Actions;

use App\Core\Enums\AppPermission;
use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingCustomEquipmentRequest;
use App\Modules\Booking\Services\BookingAvailabilityResolver;
use App\Modules\Scenario\Models\Scenario;
use App\Modules\SimResource\Enums\SimResourceKind;
use App\Modules\SimResource\Enums\SimResourceStatus;
use App\Modules\SimResource\Models\SimResource;
use App\Modules\Simulator\Services\SimulatorAssetLocker;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateBookingAction
{
    public function __construct(
        private readonly BookingAvailabilityResolver $availabilityResolver,
        private readonly SimulatorAssetLocker $simulatorLocker,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $actor): Booking
    {
        if (
            ! $actor->canAccess(AppPermission::BookingCreate)
            || $actor->college_id === null
        ) {
            abort(403);
        }

        $requestedResources = collect($data['resources']);
        $resourceIds = $requestedResources
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        $quantities = $requestedResources
            ->mapWithKeys(
                static fn (array $resource): array => [
                    (int) $resource['id'] => (int) $resource['quantity'],
                ],
            )
            ->all();

        $startsAt = CarbonImmutable::parse((string) $data['starts_at']);
        $endsAt = CarbonImmutable::parse((string) $data['ends_at']);
        $recommendedIds = [];

        if (! empty($data['scenario_id'])) {
            $scenario = Scenario::query()
                ->whereKey($data['scenario_id'])
                ->where('is_active', true)
                ->whereHas('course', fn ($query) => $query->where('college_id', $actor->college_id))
                ->first();

            if (! $scenario) {
                throw ValidationException::withMessages([
                    'scenario_id' => 'สถานการณ์จำลองที่เลือกไม่พร้อมใช้งาน',
                ]);
            }

            $recommendedIds = $scenario->recommendedResources()
                ->where('sim_resources.kind', SimResourceKind::Equipment)
                ->where('sim_resources.college_id', $actor->college_id)
                ->pluck('sim_resources.id')
                ->map(static fn (int $id): int => $id)
                ->all();
        }

        return DB::transaction(function () use (
            $data,
            $actor,
            $resourceIds,
            $quantities,
            $startsAt,
            $endsAt,
            $recommendedIds,
        ): Booking {
            $resources = SimResource::query()
                ->whereIn('id', $resourceIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($resources->count() !== count($resourceIds)) {
                throw ValidationException::withMessages([
                    'resources' => 'ไม่พบทรัพยากรที่เลือกบางรายการ',
                ]);
            }

            if (
                $resources->where('kind', SimResourceKind::Room)->count() !== 1
            ) {
                throw ValidationException::withMessages([
                    'resources' => 'คำขอจองต้องเลือกห้องปฏิบัติการ 1 ห้อง',
                ]);
            }

            foreach ($resources as $resource) {
                if (
                    $resource->college_id !== $actor->college_id
                    || $resource->status !== SimResourceStatus::Ready
                ) {
                    throw ValidationException::withMessages([
                        'resources' => 'ทรัพยากรที่เลือกไม่พร้อมให้จองหรือไม่อยู่ในหน่วยงานของคุณ',
                    ]);
                }

                $requestedQuantity = $quantities[$resource->id] ?? 0;

                if (
                    $requestedQuantity < 1
                    || ($resource->is_exclusive && $requestedQuantity !== 1)
                    || $requestedQuantity > $resource->quantity_total
                ) {
                    throw ValidationException::withMessages([
                        'resources' => sprintf(
                            'จำนวนที่ขอใช้สำหรับ "%s" ไม่ถูกต้อง',
                            $resource->name,
                        ),
                    ]);
                }
            }

            $this->availabilityResolver->ensureAvailable(
                resources: $resources,
                requestedQuantities: $quantities,
                startsAt: $startsAt,
                endsAt: $endsAt,
            );

            if (! empty($data['simulator_asset_id'])) {
                $simulator = $this->simulatorLocker->eligible((int) $data['simulator_asset_id'], $actor->college_id);
                $this->availabilityResolver->ensureSimulatorAvailable($simulator, $startsAt, $endsAt);
            }

            $booking = Booking::query()->create([
                'college_id' => $actor->college_id,
                'requested_by_user_id' => $actor->id,
                'course_id' => $data['course_id'] ?? null,
                'scenario_id' => $data['scenario_id'] ?? null,
                'simulator_asset_id' => $data['simulator_asset_id'] ?? null,
                'requester_name' => $actor->name,
                'requester_phone' => $data['requester_phone'] ?? null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'participant_count' => $data['participant_count'] ?? null,
                'note' => $data['note'] ?? null,
                'status' => BookingStatus::Pending,
            ]);

            $booking->resources()->attach(
                collect($quantities)
                    ->mapWithKeys(
                        static fn (int $quantity, int $resourceId): array => [
                            $resourceId => [
                                'quantity' => $quantity,
                                'is_auto_recommended' => in_array($resourceId, $recommendedIds, true),
                            ],
                        ],
                    )
                    ->all(),
            );

            foreach ($data['custom_equipment'] ?? [] as $customEquipment) {
                BookingCustomEquipmentRequest::query()->create([
                    'booking_id' => $booking->id,
                    'name' => $customEquipment['name'],
                    'quantity' => $customEquipment['quantity'],
                    'note' => $customEquipment['note'] ?? null,
                ]);
            }

            $booking->statusTransitions()->create([
                'from_status' => null,
                'to_status' => BookingStatus::Pending,
                'actor_user_id' => $actor->id,
                'reason' => null,
            ]);

            return $booking->load('resources');
        }, attempts: 3);
    }
}
