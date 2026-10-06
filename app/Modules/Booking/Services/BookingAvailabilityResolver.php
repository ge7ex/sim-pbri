<?php

namespace App\Modules\Booking\Services;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\SimResource\Models\SimResource;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class BookingAvailabilityResolver
{
    /**
     * @param Collection<int, SimResource> $resources
     * @param array<int, int> $requestedQuantities
     */
    public function ensureAvailable(
        Collection $resources,
        array $requestedQuantities,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ?int $ignoreBookingId = null,
    ): void {
        foreach ($resources as $resource) {
            $query = DB::table('booking_resource')
                ->join('bookings', 'bookings.id', '=', 'booking_resource.booking_id')
                ->where('booking_resource.sim_resource_id', $resource->id)
                ->whereIn('bookings.status', [
                    BookingStatus::Pending->value,
                    BookingStatus::Approved->value,
                ])
                ->where('bookings.starts_at', '<', $endsAt)
                ->where('bookings.ends_at', '>', $startsAt);

            if ($ignoreBookingId !== null) {
                $query->where('bookings.id', '!=', $ignoreBookingId);
            }

            $requestedQuantity = $requestedQuantities[$resource->id] ?? 0;

            if ($resource->is_exclusive) {
                if ($query->exists()) {
                    $this->throwOverlap();
                }

                continue;
            }

            $reservedQuantity = (int) $query->sum('booking_resource.quantity');

            if ($reservedQuantity + $requestedQuantity > $resource->quantity_total) {
                throw ValidationException::withMessages([
                    'resources' => sprintf(
                        'ทรัพยากร "%s" มีจำนวนคงเหลือไม่เพียงพอในช่วงเวลาที่เลือก',
                        $resource->name,
                    ),
                ]);
            }
        }
    }

    private function throwOverlap(): never
    {
        throw ValidationException::withMessages([
            'starts_at' => 'ช่วงเวลาที่เลือกทับซ้อนกับคำขอหรือการจองที่มีอยู่แล้ว',
        ]);
    }
}
