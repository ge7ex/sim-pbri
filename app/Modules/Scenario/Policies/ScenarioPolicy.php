<?php

namespace App\Modules\Scenario\Policies;

use App\Core\Enums\AppPermission;
use App\Models\User;
use App\Modules\Scenario\Models\Scenario;

final class ScenarioPolicy
{
    public function update(User $user, Scenario $scenario): bool
    {
        $scenario->loadMissing('course');

        return $user->canAccess(AppPermission::ScenarioUpdate)
            && $scenario->course->college_id === $user->college_id;
    }
}
