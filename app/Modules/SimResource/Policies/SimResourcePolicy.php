<?php

namespace App\Modules\SimResource\Policies;

use App\Core\Enums\AppPermission;
use App\Models\User;
use App\Modules\SimResource\Models\SimResource;

final class SimResourcePolicy
{
    public function view(User $user, SimResource $resource): bool
    {
        return $user->canAccess(AppPermission::ResourceView)
            && $resource->college_id === $user->college_id;
    }

    public function create(User $user): bool
    {
        return $user->canAccess(AppPermission::ResourceCreate);
    }

    public function update(User $user, SimResource $resource): bool
    {
        return $user->canAccess(AppPermission::ResourceUpdate)
            && $resource->college_id === $user->college_id;
    }
}
