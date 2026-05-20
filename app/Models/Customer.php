<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    protected $guarded = [];

    protected $hidden = ['password'];

    protected $casts = [
        'must_change_password' => 'boolean',
        'last_login_at'        => 'datetime',
    ];

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

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'recipient_id')
                    ->where('recipient_type', 'customer')
                    ->orderByDesc('created_at');
    }

    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->where('status', 'pending')->count();
    }
}
