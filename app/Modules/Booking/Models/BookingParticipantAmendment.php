<?php

namespace App\Modules\Booking\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BookingParticipantAmendment extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['previous_count', 'participant_count', 'actor_user_id', 'reason'];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
