<?php

namespace App\Modules\Booking\Services;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\SimResource\Models\SimResource;
use App\Modules\Simulator\Models\SimulatorAsset;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class BookingAvailabilityResolver
{
    /**
     * @param  Collection<int, SimResource>  $resources
     * @param  array<int, int>  $requestedQuantities
     */
    public function ensureAvailable(
        Collection $resources,
        array $requestedQuantities,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ?int $ignoreBookingId = null,
    ): void {
        foreach ($resources as $resource) {
            $query = $this->blockingBookings($startsAt, $endsAt, $ignoreBookingId)
                ->join('booking_resource', 'bookings.id', '=', 'booking_resource.booking_id')
                ->where('booking_resource.sim_resource_id', $resource->id);

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

    public function ensureSimulatorAvailable(SimulatorAsset $asset, CarbonInterface $startsAt, CarbonInterface $endsAt, ?int $ignoreBookingId = null): void
    {
        if ($this->blockingBookings($startsAt, $endsAt, $ignoreBookingId)->where('simulator_asset_id', $asset->id)->exists()) {
            throw ValidationException::withMessages(['simulator_asset_id' => 'หุ่นจำลองนี้มีคำขอหรือการจองในช่วงเวลาที่เลือกแล้ว']);
        }
    }

    /** @return list<int> */
    public function unavailableSimulatorIds(array $ids, CarbonInterface $startsAt, CarbonInterface $endsAt): array
    {
        return $this->blockingBookings($startsAt, $endsAt)->whereIn('simulator_asset_id', $ids)
            ->distinct()->pluck('simulator_asset_id')->map(static fn ($id): int => (int) $id)->all();
    }

    /** @return list<int> */
    public function unavailableResourceIds(array $ids, CarbonInterface $startsAt, CarbonInterface $endsAt): array
    {
        return $this->blockingBookings($startsAt, $endsAt)
            ->join('booking_resource', 'bookings.id', '=', 'booking_resource.booking_id')
            ->whereIn('booking_resource.sim_resource_id', $ids)
            ->distinct()
            ->orderBy('booking_resource.sim_resource_id')
            ->pluck('booking_resource.sim_resource_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    private function blockingBookings(CarbonInterface $startsAt, CarbonInterface $endsAt, ?int $ignoreBookingId = null): Builder
    {
        return DB::table('bookings')
            ->whereIn('bookings.status', [BookingStatus::Pending->value, BookingStatus::Approved->value])
            ->where('bookings.starts_at', '<', $endsAt)
            ->where('bookings.ends_at', '>', $startsAt)
            ->when($ignoreBookingId !== null, fn ($q) => $q->where('bookings.id', '!=', $ignoreBookingId));
    }

    private function throwOverlap(): never
    {
        throw ValidationException::withMessages([
            'starts_at' => 'ช่วงเวลาที่เลือกทับซ้อนกับคำขอหรือการจองที่มีอยู่แล้ว',
        ]);
    }
}
