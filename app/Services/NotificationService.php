<?php

namespace App\Services;

use App\Jobs\SendNotificationEmail;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Staff;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send an in-app notification and optionally dispatch an email job.
     *
     * @param  int    $recipientId    Customer or Staff id
     * @param  string $recipientType  'customer' | 'staff'
     * @param  string $message        Plain-text body for in-app display
     * @param  string $subject        Email subject line
     * @param  string $mailView       Blade view under resources/views/mail/ (without extension)
     * @param  array  $mailData       Variables passed to the Blade view
     */
    public function send(
        int    $recipientId,
        string $recipientType,
        string $message,
        string $subject  = '',
        string $mailView = '',
        array  $mailData = []
    ): void {
        try {
            // 1 — Persist in-app notification
            $notification = Notification::create([
                'recipient_id'   => $recipientId,
                'recipient_type' => $recipientType,
                'channel'        => 'in_app',
                'message'        => $message,
                'status'         => 'pending',
            ]);

            // 2 — Dispatch email if a view is specified and the recipient has an email address
            if ($mailView && $subject) {
                $email = $this->resolveEmail($recipientId, $recipientType);
                if ($email) {
                    SendNotificationEmail::dispatch(
                        $notification->id,
                        $email,
                        $subject,
                        $mailView,
                        $mailData
                    );
                }
            }
        } catch (\Throwable $e) {
            // Notifications must never crash a business transaction
            Log::error('NotificationService::send failed', [
                'recipient_id'   => $recipientId,
                'recipient_type' => $recipientType,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    private function resolveEmail(int $id, string $type): ?string
    {
        return match ($type) {
            'customer' => Customer::find($id)?->email,
            'staff'    => Staff::find($id)?->email,
            default    => null,
        };
    }
}
