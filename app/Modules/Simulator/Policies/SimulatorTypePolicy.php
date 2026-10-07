<?php

namespace App\Modules\Simulator\Policies;

use App\Core\Enums\AppPermission;
use App\Models\User;
use App\Modules\Simulator\Models\SimulatorType;

final class SimulatorTypePolicy
{
    public function view(User $user, SimulatorType $record): bool
    {
        return $user->college_id !== null
            && $user->canAccess(AppPermission::SimulatorView)
            && $record->college_id === $user->college_id;
    }

    public function create(User $user): bool
    {
        return $user->college_id !== null && $user->canAccess(AppPermission::SimulatorCreate);
    }

    public function update(User $user, SimulatorType $record): bool
    {
        return $user->college_id !== null
            && $user->canAccess(AppPermission::SimulatorUpdate)
            && $record->college_id === $user->college_id;
    }
}
