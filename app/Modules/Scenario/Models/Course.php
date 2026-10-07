<?php

namespace App\Modules\Scenario\Models;

use App\Models\College;
use App\Modules\Booking\Models\Booking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Course extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'college_id',
        'code',
        'name',
    ];

    /**
     * @return BelongsTo<College, $this>
     */
    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    /**
     * @return HasMany<Scenario, $this>
     */
    public function scenarios(): HasMany
    {
        return $this->hasMany(Scenario::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
