<?php

namespace App\Modules\SimResource\Models;

use App\Models\College;
use App\Modules\Booking\Models\Booking;
use App\Modules\SimResource\Enums\SimResourceKind;
use App\Modules\SimResource\Enums\SimResourceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class SimResource extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'college_id',
        'name',
        'kind',
        'status',
        'quantity_total',
        'is_exclusive',
        'location',
        'description',
    ];

    /**
     * @return BelongsTo<College, $this>
     */
    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    /**
     * @return BelongsToMany<Booking, $this>
     */
    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(
            Booking::class,
            'booking_resource',
        )->withPivot('quantity')->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => SimResourceKind::class,
            'status' => SimResourceStatus::class,
            'quantity_total' => 'integer',
            'is_exclusive' => 'boolean',
        ];
    }
}
