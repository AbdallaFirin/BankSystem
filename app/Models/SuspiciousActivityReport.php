<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuspiciousActivityReport extends Model
{
    protected $table = 'suspicious_activity_reports';
    protected $guarded = [];
    protected $casts = ['reviewed_at' => 'datetime'];

    public function reporter()
    {
        return $this->belongsTo(Staff::class, 'reported_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(Staff::class, 'reviewed_by');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
