<?php

namespace App\Modules\Scenario\Http\Requests;

use App\Modules\Scenario\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $course = $this->route('course');

        return $user !== null
            && $course instanceof Course
            && $user->can('update', $course);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Course $course */
        $course = $this->route('course');

        return [
            'code' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('courses', 'code')
                    ->where('college_id', $this->user()?->college_id)
                    ->ignore($course->id),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
