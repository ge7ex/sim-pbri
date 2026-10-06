<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Booking\Models\Booking;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Database\Eloquent\Relations\HasMany;

class College extends Model
{
    /** @use HasFactory<\Database\Factories\CollegeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<SimResource, $this>
     */
    public function simResources(): HasMany
    {
        return $this->hasMany(SimResource::class);
    }
}
