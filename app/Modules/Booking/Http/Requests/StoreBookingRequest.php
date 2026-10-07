<?php

namespace App\Modules\Booking\Http\Requests;

use App\Core\Enums\AppPermission;
use App\Modules\Scenario\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'course_id' => [
                'nullable',
                'integer',
                Rule::exists('courses', 'id')->where('college_id', $this->user()?->college_id),
            ],
            'scenario_id' => [
                'nullable',
                'integer',
                Rule::exists('scenarios', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->whereIn(
                        'course_id',
                        Course::query()
                            ->select('id')
                            ->where('college_id', $this->user()?->college_id),
                    )),
            ],
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
            'custom_equipment' => ['nullable', 'array', 'max:50'],
            'custom_equipment.*.name' => ['required', 'string', 'max:255'],
            'custom_equipment.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'custom_equipment.*.note' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'participant_count' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'requester_phone' => ['nullable', 'string', 'max:32'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
