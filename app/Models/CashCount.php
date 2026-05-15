<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashCount extends Model
{
    protected $guarded = [];

    protected $casts = [
        'counted_at'     => 'datetime',
        'verified_at'    => 'datetime',
        'physical_total' => 'float',
        'system_balance' => 'float',
        'difference'     => 'float',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(Staff::class, 'verified_by');
    }

    public function denominations()
    {
        return $this->hasMany(CashCountDenomination::class)->orderByDesc('denomination');
    }
}
