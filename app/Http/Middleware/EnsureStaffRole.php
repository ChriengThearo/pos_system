<?php

namespace App\Http\Middleware;

use App\Support\StaffAuth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffRole
{
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        if (! StaffAuth::check()) {
            if ($request->expectsJson()) {
                abort(401, 'Authentication required.');
            }

            return redirect()
                ->route('staff.login')
                ->with('error', 'Please log in first.');
        }

        $allowedRoles = collect(explode(',', $roles))
            ->map(static fn (string $role): string => trim($role))
            ->filter()
            ->all();

        if (! StaffAuth::hasAnyRole($allowedRoles)) {
            if ($request->expectsJson()) {
                abort(403, 'Your role cannot access this resource.');
            }

            return redirect()
                ->route('dashboard.entry')
                ->with('error', 'Role access denied for this section.');
        }

        return $next($request);
    }
}
