<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterBranchClearing extends Model
{
    protected $table = 'inter_branch_clearing';

    protected $fillable = [
        'from_branch_id',
        'to_branch_id',
        'transaction_id',
        'amount',
        'status',
        'settled_at',
        'settled_by',
        'notes',
    ];

    protected $casts = [
        'settled_at' => 'datetime',
        'amount'     => 'decimal:2',
    ];

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function settledBy()
    {
        return $this->belongsTo(Staff::class, 'settled_by');
    }
}
