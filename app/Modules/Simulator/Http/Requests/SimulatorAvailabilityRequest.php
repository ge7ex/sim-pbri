<?php

namespace App\Modules\Simulator\Http\Requests;

use App\Core\Enums\AppPermission;
use Illuminate\Foundation\Http\FormRequest;

final class SimulatorAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess(AppPermission::BookingCreate) === true;
    }

    public function rules(): array
    {
        return ['starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after:starts_at']];
    }
}
