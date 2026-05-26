<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Users belonging to this branch.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Manager / head user of this branch (legacy single-user relation).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Warehouses of this branch.
     */
    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    /**
     * Customers belonging to this branch.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Vendors belonging to this branch.
     */
    public function vendors()
    {
        return $this->hasMany(Vendor::class);
    }
}
