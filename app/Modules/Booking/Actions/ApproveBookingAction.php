<?php

namespace App\Modules\Booking\Actions;

use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingAvailabilityResolver;
use App\Modules\SimResource\Enums\SimResourceStatus;
use App\Modules\Simulator\Services\SimulatorAssetLocker;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ApproveBookingAction
{
    public function __construct(
        private readonly BookingAvailabilityResolver $availabilityResolver,
        private readonly SimulatorAssetLocker $simulatorLocker,
    ) {}

    public function execute(Booking $booking, User $actor): Booking
    {
        return DB::transaction(function () use ($booking, $actor): Booking {
            $locked = Booking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== BookingStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'คำขอนี้ไม่ได้อยู่ในสถานะรอตรวจสอบ',
                ]);
            }

            $resources = $locked->resources()
                ->orderBy('sim_resources.id')
                ->lockForUpdate()
                ->get();

            if ($resources->contains(
                fn ($resource): bool => $resource->status !== SimResourceStatus::Ready,
            )) {
                throw ValidationException::withMessages([
                    'resources' => 'มีทรัพยากรในคำขอที่ไม่พร้อมใช้งาน',
                ]);
            }

            $quantities = $resources
                ->mapWithKeys(
                    static fn ($resource): array => [
                        $resource->id => (int) $resource->pivot->quantity,
                    ],
                )
                ->all();

            if ($locked->simulator_asset_id !== null) {
                $simulator = $this->simulatorLocker->eligible($locked->simulator_asset_id, $locked->college_id);
            }

            $this->availabilityResolver->ensureAvailable(
                resources: $resources,
                requestedQuantities: $quantities,
                startsAt: $locked->starts_at,
                endsAt: $locked->ends_at,
                ignoreBookingId: $locked->id,
            );

            if ($locked->simulator_asset_id !== null) {
                $this->availabilityResolver->ensureSimulatorAvailable($simulator, $locked->starts_at, $locked->ends_at, $locked->id);
            }

            $locked->forceFill([
                'status' => BookingStatus::Approved,
                'reviewed_by_user_id' => $actor->id,
                'reviewed_at' => now(),
                'review_reason' => null,
            ])->save();

            $locked->statusTransitions()->create([
                'from_status' => BookingStatus::Pending,
                'to_status' => BookingStatus::Approved,
                'actor_user_id' => $actor->id,
                'reason' => null,
            ]);

            return $locked->refresh();
        }, attempts: 3);
    }
}
