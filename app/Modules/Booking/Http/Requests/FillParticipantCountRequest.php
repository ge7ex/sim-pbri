<?php

namespace App\Modules\Booking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class FillParticipantCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('fillParticipantCount', $this->route('booking'));
    }

    public function rules(): array
    {
        return [
            'participant_count' => ['required', 'integer', 'min:1', 'max:10000'],
            'reason' => ['required', 'string', 'max:2000', 'regex:/\\S/u'],
        ];
    }
}
