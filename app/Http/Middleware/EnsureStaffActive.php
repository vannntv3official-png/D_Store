<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->guest(route('admin.login'));
        }

        if (!$user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->withErrors([
                'email' => 'Your staff account has been deactivated. Please contact an administrator.',
            ]);
        }

        // Must have at least one assigned role to enter admin portal
        if ($user->roles()->count() === 0) {
            abort(403, 'Unauthorized. Staff privileges required.');
        }

        return $next($request);
    }
}
