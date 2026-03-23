<?php

namespace App\Http\Middleware;

use App\Support\StaffAuth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! StaffAuth::check()) {
            if ($request->expectsJson()) {
                abort(401, 'Authentication required.');
            }

            return redirect()
                ->route('staff.login')
                ->with('error', 'Please log in with an authorized staff account.');
        }

        return $next($request);
    }
}
