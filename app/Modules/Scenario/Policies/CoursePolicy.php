<?php

namespace App\Modules\Scenario\Policies;

use App\Core\Enums\AppPermission;
use App\Models\User;
use App\Modules\Scenario\Models\Course;

final class CoursePolicy
{
    public function update(User $user, Course $course): bool
    {
        return $user->canAccess(AppPermission::ScenarioUpdate)
            && $course->college_id === $user->college_id;
    }
}
