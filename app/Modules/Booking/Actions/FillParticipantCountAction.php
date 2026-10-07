<?php

namespace App\Modules\Booking\Actions;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\RoomBookingEligibility;
use App\Modules\SimResource\Enums\SimResourceKind;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class FillParticipantCountAction
{
    public function __construct(private readonly RoomBookingEligibility $roomEligibility) {}

    public function execute(Booking $booking, User $actor, int $count, string $reason): Booking
    {
        return DB::transaction(function () use ($booking, $actor, $count, $reason): Booking {
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('fillParticipantCount', $locked);
            $resources = $locked->resources()->orderBy('sim_resources.id')->lockForUpdate()->get();
            $rooms = $resources->where('kind', SimResourceKind::Room);
            if ($rooms->count() !== 1) {
                throw ValidationException::withMessages(['resources' => 'คำขอต้องมีห้องเดียวเพื่อเติมจำนวนผู้เข้าใช้งาน']);
            }
            $this->roomEligibility->assertEligible($rooms->first(), $locked->college_id, $count);
            if ($count > 10000 || trim($reason) === '' || mb_strlen($reason) > 2000) {
                throw ValidationException::withMessages(['reason' => 'กรุณาระบุข้อมูลและเหตุผลที่ถูกต้อง']);
            }
            $locked->participantAmendments()->create([
                'previous_count' => null,
                'participant_count' => $count,
                'actor_user_id' => $actor->id,
                'reason' => trim($reason),
            ]);
            $locked->forceFill(['participant_count' => $count])->save();

            return $locked;
        }, attempts: 3);
    }
}
