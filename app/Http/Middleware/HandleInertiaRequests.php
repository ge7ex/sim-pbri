<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $user?->loadMissing('college');

        $role = $user?->accessRole();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user === null
                    ? null
                    : [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $role?->value,
                        'role_label' => $role?->label(),
                        'college' => $user->college === null
                            ? null
                            : [
                                'id' => $user->college->id,
                                'name' => $user->college->name,
                            ],
                        'has_access_profile' => $user->hasAccessProfile(),
                    ],
                'permissions' => $user?->permissionNames() ?? [],
            ],
        ];
    }
}
