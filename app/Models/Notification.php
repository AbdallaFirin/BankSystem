<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Scope to unread (pending) notifications for in-app display.
     */
    public function scopeUnread($query)
    {
        return $query->where('channel', 'in_app')->where('status', 'pending');
    }

    /**
     * Scope to a specific recipient.
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('recipient_type', 'customer')->where('recipient_id', $customerId);
    }
}
