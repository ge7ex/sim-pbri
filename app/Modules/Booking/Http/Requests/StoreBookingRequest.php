<?php

namespace App\Modules\Booking\Http\Requests;

use App\Core\Enums\AppPermission;
use Illuminate\Foundation\Http\FormRequest;

final class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess(AppPermission::BookingCreate) === true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'resources' => ['required', 'array', 'min:1'],
            'resources.*.id' => [
                'required',
                'integer',
                'distinct',
                'exists:sim_resources,id',
            ],
            'resources.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'participant_count' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'requester_phone' => ['nullable', 'string', 'max:32'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
