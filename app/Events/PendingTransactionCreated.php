<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PendingTransactionCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $transaction;
    public int   $branchId;

    public function __construct(array $transaction, int $branchId)
    {
        $this->transaction = $transaction;
        $this->branchId    = $branchId;
    }

    /** Broadcast on a public branch-scoped channel */
    public function broadcastOn(): array
    {
        return [
            new Channel("branch.{$this->branchId}.approvals"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'transaction.pending';
    }

    public function broadcastWith(): array
    {
        return ['transaction' => $this->transaction];
    }
}
