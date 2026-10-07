<?php

namespace App\Modules\Scenario\Actions;

use App\Modules\Scenario\Models\Course;

final class UpdateCourseAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(Course $course, array $data): Course
    {
        $course->update([
            'code' => $data['code'] ?? null,
            'name' => $data['name'],
        ]);

        return $course->refresh();
    }
}
