<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\JWTGuard;

class CheckRole
{
    protected function guard(): JWTGuard
    {
        return auth('api');
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $this->guard()->user();

        if (!$user || !in_array($user->role, $roles)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        return $next($request);
    }
}
