<?php

namespace App\Modules\Simulator\Services;

use App\Modules\Simulator\Enums\SimulatorAssetStatus;
use App\Modules\Simulator\Models\SimulatorAsset;
use App\Modules\Simulator\Models\SimulatorType;
use Illuminate\Validation\ValidationException;

final class SimulatorAssetLocker
{
    /** Called within the booking transaction after resource locks, before any
     * nonlocking availability read. Asset then Type is also the management order.
     * Under REPEATABLE READ, an earlier plain read would retain a stale snapshot
     * even after waiting for these locks. Do not introduce a preliminary lookup.
     */
    public function eligible(int $assetId, int $collegeId): SimulatorAsset
    {
        $asset = SimulatorAsset::whereKey($assetId)->where('college_id', $collegeId)->lockForUpdate()->first();
        if (! $asset) {
            $this->unavailable();
        }
        $type = SimulatorType::whereKey($asset->simulator_type_id)
            ->where('college_id', $collegeId)->lockForUpdate()->first();
        if (! $type || ! $asset || ! $type->is_active
            || $asset->simulator_type_id !== $type->id || $asset->status !== SimulatorAssetStatus::Active) {
            $this->unavailable();
        }

        return $asset;
    }

    private function unavailable(): never
    {
        throw ValidationException::withMessages(['simulator_asset_id' => 'หุ่นจำลองที่เลือกไม่พร้อมใช้งาน กรุณาตรวจสอบรายการอีกครั้ง']);
    }
}
