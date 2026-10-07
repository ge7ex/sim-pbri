<?php

namespace App\Modules\Scenario\Http\Requests;

use App\Modules\Scenario\Models\Scenario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateScenarioSimulatorTypesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $scenario = $this->route('scenario');

        return $scenario instanceof Scenario && $this->user()?->can('update', $scenario) === true;
    }

    public function rules(): array
    {
        return [
            'simulator_type_ids' => ['present', 'array', 'max:50'],
            'simulator_type_ids.*' => ['required', 'integer', 'distinct', Rule::exists('simulator_types', 'id')->where('college_id', $this->user()?->college_id)],
        ];
    }
}
