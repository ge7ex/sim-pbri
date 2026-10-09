<?php

namespace App\Modules\Report\Http\Requests;

use App\Core\Enums\AppPermission;
use App\Modules\Booking\Enums\BookingStatus;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess(AppPermission::ReportView) === true;
    }

    protected function prepareForValidation(): void
    {
        $now = CarbonImmutable::now(config('app.timezone'));
        $this->merge([
            'date_from' => $this->input('date_from') ?: $now->startOfMonth()->toDateString(),
            'date_to' => $this->input('date_to') ?: $now->endOfMonth()->toDateString(),
        ]);
    }

    public function rules(): array
    {
        $college = $this->user()->college_id;

        return [
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'room_id' => ['nullable', 'integer', Rule::exists('sim_resources', 'id')->where('college_id', $college)->where('kind', 'room')],
            'simulator_asset_id' => ['nullable', 'integer', Rule::exists('simulator_assets', 'id')->where('college_id', $college)],
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')->where('college_id', $college)],
            'scenario_id' => ['nullable', 'integer', Rule::exists('scenarios', 'id')->whereIn('course_id', fn ($query) => $query->select('id')->from('courses')->where('college_id', $college))],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('date_from') || $validator->errors()->has('date_to')) {
                return;
            }
            $from = CarbonImmutable::parse($this->input('date_from'), config('app.timezone'));
            $to = CarbonImmutable::parse($this->input('date_to'), config('app.timezone'));
            if ($from->diffInDays($to) > 365) {
                $validator->errors()->add('date_to', 'กรุณาเลือกช่วงวันที่ไม่เกิน 366 วัน');
            }
        }];
    }

    public function filters(): array
    {
        $data = $this->validated();

        return [
            'date_from' => $data['date_from'], 'date_to' => $data['date_to'], 'status' => $data['status'] ?? null,
            'room_id' => isset($data['room_id']) ? (int) $data['room_id'] : null,
            'simulator_asset_id' => isset($data['simulator_asset_id']) ? (int) $data['simulator_asset_id'] : null,
            'course_id' => isset($data['course_id']) ? (int) $data['course_id'] : null,
            'scenario_id' => isset($data['scenario_id']) ? (int) $data['scenario_id'] : null,
        ];
    }
}
