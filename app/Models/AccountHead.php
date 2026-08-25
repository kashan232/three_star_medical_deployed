<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountHead extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    public function accounts()
    {
        return $this->hasMany(Account::class, 'head_id');
    }

    public function parent()
    {
        return $this->belongsTo(AccountHead::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(AccountHead::class, 'parent_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
