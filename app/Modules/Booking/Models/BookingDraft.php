<?php

namespace App\Modules\Booking\Models;

use Illuminate\Database\Eloquent\Model;

final class BookingDraft extends Model
{
    protected $fillable = ['college_id', 'user_id', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
