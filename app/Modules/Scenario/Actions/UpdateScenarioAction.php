<?php

namespace App\Modules\Scenario\Actions;

use App\Modules\Scenario\Models\Course;
use App\Modules\Scenario\Models\Scenario;

final class UpdateScenarioAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(Scenario $scenario, Course $preferredCourse, array $data): Scenario
    {
        $scenario->update([
            'course_id' => $preferredCourse->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'],
        ]);

        return $scenario->refresh();
    }
}
