<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashCountDenomination extends Model
{
    protected $guarded = [];

    protected $casts = [
        'denomination' => 'float',
        'subtotal'     => 'float',
    ];

    public function cashCount()
    {
        return $this->belongsTo(CashCount::class);
    }
}
