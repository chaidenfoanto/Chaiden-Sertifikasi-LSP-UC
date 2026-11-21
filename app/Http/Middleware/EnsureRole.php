<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to users with the given role (admin or renter).
 */
class EnsureRole
{
    /**
     * Abort with 403 when the authenticated user does not have the role.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user === null || $user->role !== $role) {
            abort(403, 'You are not allowed to access this page.');
        }

        return $next($request);
    }
}