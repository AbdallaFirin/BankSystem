<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $table = 'accounts';
    protected $guarded = [];
    protected $appends = ['balance'];
    protected $casts = [
        'is_frozen'  => 'boolean',
        'frozen_at'  => 'datetime',
        'opened_at'  => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Dynamically compute balance: SUM(Credits) - SUM(Debits) in one query.
     */
    public function getBalanceAttribute(): float
    {
        return (float) LedgerEntry::where('account_id', $this->id)
            ->selectRaw("SUM(CASE WHEN entry_type = 'credit' THEN amount ELSE -amount END) as bal")
            ->value('bal') ?? 0.0;
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

    public function freezeLogs()
    {
        return $this->hasMany(AccountFreezeLog::class)->orderByDesc('created_at');
    }
}
