<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reads the JWT from the HttpOnly cookie and hands it to the api guard.
 *
 * It never blocks a request. An invalid or expired token simply leaves the
 * visitor as a guest, and protected routes are guarded by the "auth" middleware.
 */
class AttachJwtFromCookie
{
    /**
     * Attach the cookie token to the api guard when present.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('access_token');

        if (is_string($token) && $token !== '') {
            auth('api')->setToken($token);
        }

        return $next($request);
    }
}