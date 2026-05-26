<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminOnly
{
    /**
     * Only allow super admin users through.
     * Redirects regular branch users with 403.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->isSuperAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized. Super admin access required.'], 403);
            }

            abort(403, 'Unauthorized. Super admin access required.');
        }

        return $next($request);
    }
}
