<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Let the request through only if the signed-in user has one of the allowed roles.
     * Used on a route as: role:parent  (or role:parent,student for several roles).
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // The jwt.auth middleware stored the signed-in user on the request (it must run first).
        $user = $request->attributes->get('auth_user');

        // No user, or a role that is not in the allowed list: stop with a 403 "forbidden" error.
        if ($user === null || ! in_array($user->role, $roles, true)) {
            abort(403, 'You do not have access to this page.');
        }

        // The role is allowed: continue to the controller.
        return $next($request);
    }
}