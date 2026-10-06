<?php

namespace App\Modules\Booking\Actions;

use App\Core\Enums\AppPermission;
use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingAvailabilityResolver;
use App\Modules\SimResource\Enums\SimResourceStatus;
use App\Modules\SimResource\Models\SimResource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateBookingAction
{
    public function __construct(
        private readonly BookingAvailabilityResolver $availabilityResolver,
    ) {
    }

    /**
     * @param array<string, mixed> $data
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

        return DB::transaction(function () use (
            $data,
            $actor,
            $resourceIds,
            $quantities,
            $startsAt,
            $endsAt,
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

            $booking = Booking::query()->create([
                'college_id' => $actor->college_id,
                'requested_by_user_id' => $actor->id,
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
                            $resourceId => ['quantity' => $quantity],
                        ],
                    )
                    ->all(),
            );

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
