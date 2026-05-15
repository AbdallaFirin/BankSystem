<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded = [];

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'home_branch_id');
    }

    public function registeredBy()
    {
        return $this->belongsTo(Staff::class, 'registered_by');
    }

    public function kycDocuments()
    {
        return $this->hasMany(KycDocument::class);
    }
}
