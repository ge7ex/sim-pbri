<?php

namespace App\Modules\Scenario\Actions;

use App\Modules\Scenario\Models\Course;
use App\Modules\Scenario\Models\Scenario;

final class CreateScenarioAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(Course $course, array $data): Scenario
    {
        return Scenario::query()->create([
            'course_id' => $course->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'],
        ]);
    }
}
