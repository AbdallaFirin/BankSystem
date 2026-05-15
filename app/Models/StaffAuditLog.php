<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffAuditLog extends Model
{
    protected $table = 'staff_audit_logs';

    protected $fillable = [
        'staff_id',
        'branch_id',
        'action',
        'description',
        'permission_used',
        'target_table',
        'target_id',
        'ip_address',
        'result',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
