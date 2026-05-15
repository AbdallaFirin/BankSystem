<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashAllocation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'allocated_at'   => 'datetime',
        'acknowledged_at'=> 'datetime',
        'returned_at'    => 'datetime',
        'amount'         => 'float',
    ];

    /** The vault cashier who created this allocation */
    public function allocatedBy()
    {
        return $this->belongsTo(Staff::class, 'allocated_by');
    }

    /** The teller who received the float */
    public function teller()
    {
        return $this->belongsTo(Staff::class, 'teller_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /** The virtual till account that holds the float */
    public function tillAccount()
    {
        return $this->belongsTo(Account::class, 'till_account_id');
    }
}
