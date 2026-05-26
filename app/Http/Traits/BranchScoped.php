<?php

namespace App\Http\Traits;

/**
 * BranchScoped Trait
 *
 * Use this trait in controllers that need to filter data by the logged-in user's branch.
 *
 * Usage:
 *   use BranchScoped;
 *   $query = Sale::query()->branchScoped($this->getBranchId());
 */
trait BranchScoped
{
    /**
     * Get the branch_id for the current user.
     * Returns null if user is super admin (no filter applied = sees all).
     */
    protected function getBranchId(): ?int
    {
        return auth()->user()?->getBranchId();
    }

    /**
     * Check if the current user is a super admin.
     */
    protected function isSuperAdmin(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    /**
     * Apply branch scope to a query builder.
     * If branchId is null (super admin), no filter is applied.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyBranchScope($query, ?int $branchId = null)
    {
        $branchId ??= $this->getBranchId();

        if ($branchId !== null) {
            return $query->where('branch_id', $branchId);
        }

        return $query;
    }
}
