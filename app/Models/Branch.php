<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $guarded = [];

    protected $casts = [
        'working_days'        => 'array',
        'vault_min_threshold' => 'float',
        'opened_at'           => 'datetime',
    ];

    protected $appends = ['full_code'];

    /**
     * Full routing code = branch_code prefix + branch_number.
     * e.g. "BOS" + 101 = "BOS101"
     * If the prefix hasn't been set yet, returns just the number (e.g. "101").
     */
    public function getFullCodeAttribute(): string
    {
        return ($this->branch_code ?? '') . ($this->branch_number ?? '');
    }

    public function staff()
    {
        return $this->hasMany(Staff::class);
    }

    public function accounts()
    {
        return $this->hasMany(Account::class, 'home_branch_id');
    }

    public function manager()
    {
        return $this->belongsTo(Staff::class, 'manager_id');
    }
}
