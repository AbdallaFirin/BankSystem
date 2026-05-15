<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Staff extends Authenticatable
{
    use Notifiable;

    protected $table = 'staff';

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token', 'temp_password'];

    protected $casts = [
        'date_of_birth'          => 'date',
        'hired_at'               => 'date',
        'force_password_change'  => 'boolean',
    ];

    /**
     * The column used by Laravel's Auth to look up the user.
     * We use ident_number (Staff ID) instead of email.
     */
    public function getAuthIdentifierName(): string
    {
        return 'ident_number';
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(StaffAuditLog::class);
    }
}
