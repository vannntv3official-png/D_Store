<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        // Super Admin has all permissions automatically
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        if (!$user->hasPermission($permission)) {
            abort(403, "Forbidden. Missing required permission: {$permission}");
        }

        return $next($request);
    }
}
