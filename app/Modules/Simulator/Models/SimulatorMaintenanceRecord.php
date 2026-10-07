<?php

namespace App\Modules\Simulator\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SimulatorMaintenanceRecord extends Model
{
    protected $fillable = [
        'simulator_asset_id', 'maintenance_date', 'description',
        'cost', 'performed_by', 'note', 'created_by_user_id',
    ];

    public function simulatorAsset(): BelongsTo
    {
        return $this->belongsTo(SimulatorAsset::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    protected function casts(): array
    {
        return ['maintenance_date' => 'immutable_date:Y-m-d', 'cost' => 'decimal:2'];
    }
}
