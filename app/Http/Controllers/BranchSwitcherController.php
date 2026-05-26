<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

/**
 * SuperAdmin Branch Switcher
 * 
 * Allows a super admin to switch between branches or view all branches.
 * The selected branch is stored in session as 'admin_active_branch_id'.
 * The SetActiveBranch middleware reads this to set $activeBranch view variable.
 */
class BranchSwitcherController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('super_admin');
    }

    /**
     * Switch the active branch context for super admin.
     * POST /branch/switch  { branch_id: int|null }
     */
    public function switch(Request $request)
    {
        $branchId = $request->input('branch_id');

        if ($branchId) {
            $branch = Branch::findOrFail($branchId);
            session(['super_admin_branch_id' => (int) $branchId]);
            $msg = "Switched to branch: {$branch->name}";
        } else {
            // Null = view all branches (super admin global view)
            session()->forget('super_admin_branch_id');
            $msg = "Now viewing: All Branches";
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }
}
