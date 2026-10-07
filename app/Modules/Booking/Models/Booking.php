<?php

namespace App\Modules\Booking\Models;

use App\Models\College;
use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Scenario\Models\Course;
use App\Modules\Scenario\Models\Scenario;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Booking extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'college_id',
        'requested_by_user_id',
        'course_id',
        'scenario_id',
        'requester_name',
        'requester_phone',
        'starts_at',
        'ends_at',
        'participant_count',
        'note',
        'status',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_reason',
    ];

    /**
     * @return BelongsTo<College, $this>
     */
    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Scenario, $this>
     */
    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    /**
     * @return HasMany<BookingStatusTransition, $this>
     */
    public function statusTransitions(): HasMany
    {
        return $this->hasMany(BookingStatusTransition::class);
    }

    /**
     * @return BelongsToMany<SimResource, $this>
     */
    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(
            SimResource::class,
            'booking_resource',
        )->withPivot(
            'quantity',
            'is_auto_recommended',
        )->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'reviewed_at' => 'immutable_datetime',
            'participant_count' => 'integer',
        ];
    }
}
