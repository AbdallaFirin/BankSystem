<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    protected $fillable = [
        'type_name', 'interest_rate', 'min_balance',
        'withdrawal_limit', 'overdraft_allowed', 'overdraft_limit', 'is_active',
    ];

    protected $casts = [
        'overdraft_allowed' => 'boolean',
        'is_active'         => 'boolean',
    ];
}
