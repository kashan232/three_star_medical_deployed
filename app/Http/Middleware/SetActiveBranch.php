<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetActiveBranch
{
    /**
     * Handle an incoming request.
     * Sets the active branch context for the logged-in user.
     * Super admin has no branch restriction.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            if ($user->isSuperAdmin()) {
                // Super admin: can optionally switch branches via session
                $activeBranchId = session('super_admin_branch_id');
                $activeBranch = $activeBranchId
                    ? \App\Models\Branch::find($activeBranchId)
                    : null;

                View::share('activeBranch', $activeBranch);
                View::share('isSuperAdmin', true);
                View::share('activeBranchId', $activeBranchId);
            } else {
                // Regular user: must belong to a branch
                if (! $user->branch_id) {
                    auth()->logout();

                    return redirect()->route('login')
                        ->withErrors(['email' => 'Your account is not assigned to any branch. Please contact the administrator.']);
                }

                $branch = $user->branch;

                if (! $branch || ! $branch->is_active) {
                    auth()->logout();

                    return redirect()->route('login')
                        ->withErrors(['email' => 'Your branch is inactive. Please contact the administrator.']);
                }

                // Share active branch globally to all views
                View::share('activeBranch', $branch);
                View::share('isSuperAdmin', false);
                View::share('activeBranchId', $branch->id);

                // Also make it available via request for controllers
                $request->merge(['active_branch_id' => $branch->id]);
            }
        }

        return $next($request);
    }
}
