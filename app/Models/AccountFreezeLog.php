<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountFreezeLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(Staff::class, 'performed_by');
    }
}
