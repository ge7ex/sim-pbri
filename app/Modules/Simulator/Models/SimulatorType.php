<?php

namespace App\Modules\Simulator\Models;

use App\Models\College;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SimulatorType extends Model
{
    protected $fillable = ['college_id', 'name', 'description', 'is_active'];

    protected $attributes = ['is_active' => true];

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(SimulatorAsset::class);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
