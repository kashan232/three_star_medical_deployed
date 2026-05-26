<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The branch this user belongs to. Null for super_admin.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * The employee profile associated with this user.
     */
    public function employee()
    {
        return $this->hasOne(\App\Models\Hr\Employee::class);
    }

    /**
     * Determine if this user is the super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->usertype === 'super_admin';
    }

    /**
     * Get the branch_id for this user safely.
     * Returns null for super_admin (can see all branches).
     */
    public function getBranchId(): ?int
    {
        if ($this->isSuperAdmin()) {
            return session('super_admin_branch_id');
        }

        return $this->branch_id;
    }

    /**
     * Check if user's employee profile is active (can login).
     * Returns true if user has no employee profile (admin/non-employee users).
     * Returns false if employee status is non-active or terminated.
     */
    public function isEmployeeActive()
    {
        $employee = $this->employee;

        // If no employee profile, user can login (admin users, etc.)
        if (! $employee) {
            return true;
        }

        // Only active employees can login
        return $employee->status === 'active';
    }
}
