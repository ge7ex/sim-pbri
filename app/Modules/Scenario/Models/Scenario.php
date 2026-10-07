<?php

namespace App\Modules\Scenario\Models;

use App\Modules\Booking\Models\Booking;
use App\Modules\SimResource\Models\SimResource;
use App\Modules\Simulator\Models\SimulatorType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Scenario extends Model
{
    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'course_id',
        'name',
        'description',
        'is_active',
    ];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsToMany<SimResource, $this>
     */
    public function recommendedResources(): BelongsToMany
    {
        return $this->belongsToMany(
            SimResource::class,
            'scenario_resource_templates',
        )->withPivot('quantity')->withTimestamps();
    }

    /**
     * @return BelongsToMany<SimulatorType, $this>
     */
    public function recommendedSimulatorTypes(): BelongsToMany
    {
        return $this->belongsToMany(SimulatorType::class, 'scenario_simulator_type')->withTimestamps();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
