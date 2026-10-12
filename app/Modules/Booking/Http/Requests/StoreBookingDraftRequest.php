<?php

namespace App\Modules\Booking\Http\Requests;

use App\Core\Enums\AppPermission;
use App\Modules\Scenario\Models\Course;
use App\Modules\Simulator\Models\SimulatorType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreBookingDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess(AppPermission::BookingCreate) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $collegeId = $this->user()?->college_id;

        return [
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')->where('college_id', $collegeId)],
            'scenario_id' => ['nullable', 'integer', Rule::exists('scenarios', 'id')->where(fn ($query) => $query
                ->where('is_active', true)
                ->whereIn('course_id', Course::query()->select('id')->where('college_id', $collegeId)))],
            'simulator_asset_id' => ['nullable', 'integer', Rule::exists('simulator_assets', 'id')->where(fn ($query) => $query
                ->where('college_id', $collegeId)->where('status', 'active')
                ->whereIn('simulator_type_id', SimulatorType::query()->where('college_id', $collegeId)->where('is_active', true)->select('id')))],
            'resources' => ['nullable', 'array', 'max:51'],
            'resources.*.id' => ['required', 'integer', 'distinct', Rule::exists('sim_resources', 'id')->where('college_id', $collegeId)],
            'resources.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'custom_equipment' => ['nullable', 'array', 'max:50'],
            'custom_equipment.*.name' => ['required', 'string', 'max:255'],
            'custom_equipment.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'custom_equipment.*.note' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'participant_count' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'requester_phone' => ['nullable', 'string', 'min:7', 'max:32', 'regex:/\A(?=(?:.*[0-9๐-๙]){7})[+()0-9๐-๙.\-\s]+\z/u'],
            'requester_name' => ['nullable', 'string', 'min:2', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
