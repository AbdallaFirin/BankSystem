<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $table = 'accounts';
    protected $guarded = [];
    protected $appends = ['balance'];
    protected $casts = [
        'is_frozen' => 'boolean',
        'frozen_at' => 'datetime',
    ];

    /**
     * Dynamically compute balance: SUM(Credits) - SUM(Debits).
     */
    public function getBalanceAttribute()
    {
        $credits = LedgerEntry::where('account_id', $this->id)
                    ->where('entry_type', 'credit')
                    ->sum('amount');
                    
        $debits = LedgerEntry::where('account_id', $this->id)
                    ->where('entry_type', 'debit')
                    ->sum('amount');
                    
        return $credits - $debits;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
    }

    public function homeBranch()
    {
        return $this->belongsTo(Branch::class, 'home_branch_id');
    }

    public function ledgerEntries()
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function frozenBy()
    {
        return $this->belongsTo(Staff::class, 'frozen_by');
    }
}
