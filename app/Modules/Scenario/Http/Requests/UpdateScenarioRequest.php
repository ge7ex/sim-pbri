<?php

namespace App\Modules\Scenario\Http\Requests;

use App\Modules\Scenario\Models\Scenario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateScenarioRequest extends FormRequest
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
        return [
            'course_id' => [
                'required',
                'integer',
                Rule::exists('courses', 'id')
                    ->where('college_id', $this->user()?->college_id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
