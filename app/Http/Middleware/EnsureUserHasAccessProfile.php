<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserHasAccessProfile
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user !== null && $user->hasAccessProfile(),
            Response::HTTP_FORBIDDEN,
        );

        if ($user->must_change_password && ! $request->routeIs('account.password.*')) {
            return redirect()->route('account.password.edit');
        }

        return $next($request);
    }
}
