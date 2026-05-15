<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingApproval extends Model
{
    protected $guarded = [];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(Staff::class, 'requested_by');
    }

    public function decidedBy()
    {
        return $this->belongsTo(Staff::class, 'decided_by');
    }
}
