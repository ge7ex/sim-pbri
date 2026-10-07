<?php

namespace App\Modules\Simulator\Models;

use App\Models\College;
use App\Modules\Simulator\Enums\SimulatorAssetStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SimulatorAsset extends Model
{
    protected $fillable = [
        'college_id', 'simulator_type_id', 'asset_name', 'asset_code',
        'purchase_year', 'purchase_price', 'useful_life_years',
        'status', 'location', 'description',
    ];

    protected $attributes = ['status' => 'active'];

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function simulatorType(): BelongsTo
    {
        return $this->belongsTo(SimulatorType::class);
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(SimulatorMaintenanceRecord::class);
    }

    protected function casts(): array
    {
        return [
            'status' => SimulatorAssetStatus::class,
            'purchase_year' => 'integer',
            'purchase_price' => 'decimal:2',
            'useful_life_years' => 'integer',
        ];
    }
}
