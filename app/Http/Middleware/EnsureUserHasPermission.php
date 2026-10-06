<?php

namespace App\Http\Middleware;

use App\Core\Enums\AppPermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserHasPermission
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $required = AppPermission::tryFrom($permission);

        abort_unless(
            $required !== null && $request->user()?->canAccess($required) === true,
            Response::HTTP_FORBIDDEN,
        );

        return $next($request);
    }
}
