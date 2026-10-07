<?php

namespace App\Modules\Simulator\Http\Requests;

use App\Modules\Simulator\Models\SimulatorType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveSimulatorTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = $this->route('simulatorType');

        return $type instanceof SimulatorType
            ? $this->user()?->can('update', $type) === true
            : $this->user()?->can('create', SimulatorType::class) === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('simulator_types')->where('college_id', $this->user()?->college_id)->ignore($this->route('simulatorType')?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
