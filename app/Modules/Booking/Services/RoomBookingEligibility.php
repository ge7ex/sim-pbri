<?php

namespace App\Modules\Booking\Services;

use App\Modules\SimResource\Enums\SimResourceKind;
use App\Modules\SimResource\Enums\SimResourceStatus;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Validation\ValidationException;

final class RoomBookingEligibility
{
    public function assertEligible(SimResource $room, int $collegeId, ?int $participantCount): void
    {
        if ($room->kind !== SimResourceKind::Room || $room->college_id !== $collegeId
            || $room->status !== SimResourceStatus::Ready || $room->quantity_total !== 1 || ! $room->is_exclusive) {
            throw ValidationException::withMessages(['resources' => 'ห้องไม่พร้อมใช้งานหรือไม่อยู่ในหน่วยงานของคุณ']);
        }

        if ($room->capacity === null) {
            throw ValidationException::withMessages(['resources' => 'ห้องนี้ยังไม่ได้กำหนดความจุ กรุณาติดต่อผู้ดูแลระบบ']);
        }

        if ($participantCount === null || $participantCount < 1 || $participantCount > $room->capacity) {
            throw ValidationException::withMessages(['participant_count' => 'จำนวนผู้เข้าใช้งานต้องไม่เกินความจุของห้อง']);
        }
    }
}
