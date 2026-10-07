<?php

namespace App\Modules\Simulator\Actions;

use App\Models\User;
use App\Modules\Simulator\Models\SimulatorAsset;
use App\Modules\Simulator\Models\SimulatorMaintenanceRecord;
use App\Modules\Simulator\Models\SimulatorType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ManageSimulatorAction
{
    public function saveType(array $data, User $actor, ?SimulatorType $type = null): SimulatorType
    {
        abort_unless($type ? $actor->can('update', $type) : $actor->can('create', SimulatorType::class), 403);

        return DB::transaction(function () use ($data, $actor, $type) {
            $record = $type ? SimulatorType::whereKey($type->id)->lockForUpdate()->firstOrFail() : new SimulatorType;
            $before = $record->exists ? $record->only(['name', 'description', 'is_active']) : null;
            $record->fill([
                'college_id' => $actor->college_id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'],
            ])->save();
            $this->audit($record, $actor, 'type', $before, $record->only(['name', 'description', 'is_active']));

            return $record;
        }, attempts: 3);
    }

    public function saveAsset(array $data, User $actor, ?SimulatorAsset $asset = null): SimulatorAsset
    {
        abort_unless($asset ? $actor->can('update', $asset) : $actor->can('create', SimulatorAsset::class), 403);

        return DB::transaction(function () use ($data, $actor, $asset) {
            // Existing assets are the mutex shared with booking writers.
            // No plain reads may precede the lock in a booking transaction.
            $record = $asset ? SimulatorAsset::whereKey($asset->id)->lockForUpdate()->firstOrFail() : new SimulatorAsset;
            abort_if($record->exists && $record->college_id !== $actor->college_id, 403);

            $type = SimulatorType::whereKey($data['simulator_type_id'])
                ->where('college_id', $actor->college_id)->lockForUpdate()->first();
            if (! $type) {
                throw ValidationException::withMessages(['simulator_type_id' => 'ประเภทไม่อยู่ในหน่วยงานของคุณ']);
            }

            $fields = ['simulator_type_id', 'asset_name', 'asset_code', 'purchase_year', 'purchase_price', 'useful_life_years', 'status', 'location', 'description'];
            $before = $record->exists ? $record->only($fields) : null;
            $record->fill([
                'college_id' => $actor->college_id,
                'simulator_type_id' => $type->id,
                'asset_name' => $data['asset_name'],
                'asset_code' => $data['asset_code'] ?? null,
                'purchase_year' => $data['purchase_year'] ?? null,
                'purchase_price' => $data['purchase_price'] ?? null,
                'useful_life_years' => $data['useful_life_years'] ?? null,
                'status' => $data['status'],
                'location' => $data['location'] ?? null,
                'description' => $data['description'] ?? null,
            ])->save();
            $this->audit($record, $actor, 'asset', $before, $record->only($fields));

            return $record;
        }, attempts: 3);
    }

    public function recordMaintenance(SimulatorAsset $asset, array $data, User $actor): SimulatorMaintenanceRecord
    {
        abort_unless($actor->can('maintenance', $asset), 403);

        return SimulatorMaintenanceRecord::create([
            'simulator_asset_id' => $asset->id,
            'created_by_user_id' => $actor->id,
            'maintenance_date' => $data['maintenance_date'],
            'description' => $data['description'],
            'cost' => $data['cost'] ?? null,
            'performed_by' => $data['performed_by'] ?? null,
            'note' => $data['note'] ?? null,
        ]);
    }

    private function audit(Model $record, User $actor, string $type, ?array $before, array $after): void
    {
        DB::table('simulator_change_logs')->insert([
            'college_id' => $actor->college_id,
            'actor_user_id' => $actor->id,
            'subject_type' => $type,
            'subject_id' => $record->id,
            'before_values' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
            'after_values' => json_encode($after, JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }
}
