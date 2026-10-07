<?php

namespace App\Modules\Scenario\Http\Requests;

use App\Core\Enums\AppPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess(AppPermission::ScenarioCreate) === true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $collegeId = $this->user()?->college_id;

        return [
            'code' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('courses', 'code')->where('college_id', $collegeId),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
