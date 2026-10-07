<?php

namespace App\Modules\Scenario\Actions;

use App\Models\User;
use App\Modules\Scenario\Models\Course;

final class CreateCourseAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(array $data, User $actor): Course
    {
        return Course::query()->create([
            'college_id' => $actor->college_id,
            'code' => $data['code'] ?? null,
            'name' => $data['name'],
        ]);
    }
}
