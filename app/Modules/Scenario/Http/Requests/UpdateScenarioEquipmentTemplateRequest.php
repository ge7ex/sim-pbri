<?php

namespace App\Modules\Scenario\Http\Requests;

use App\Modules\Scenario\Models\Scenario;
use App\Modules\SimResource\Enums\SimResourceKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateScenarioEquipmentTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $scenario = $this->route('scenario');

        return $user !== null
            && $scenario instanceof Scenario
            && $user->can('update', $scenario);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Scenario $scenario */
        $scenario = $this->route('scenario');
        $scenario->loadMissing('course');

        return [
            'equipment' => ['present', 'array', 'max:200'],
            'equipment.*.id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('sim_resources', 'id')
                    ->where('college_id', $scenario->course->college_id)
                    ->where('kind', SimResourceKind::Equipment->value),
            ],
            'equipment.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100000',
            ],
        ];
    }
}
