<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $guarded = [];

    public function initiator()
    {
        return $this->belongsTo(Staff::class, 'initiated_by')->with('role');
    }

    /** Customer account (for deposit/withdrawal) or sender account (for transfer) */
    public function primaryAccount()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /** Vault/till account (for deposit/withdrawal) or receiver account (for transfer) */
    public function secondaryAccount()
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }
}
