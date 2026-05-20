<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int    $tries   = 3;
    public int    $timeout = 30;

    public function __construct(
        public readonly int    $notificationId,
        public readonly string $toEmail,
        public readonly string $subject,
        public readonly string $mailView,
        public readonly array  $mailData = []
    ) {}

    public function handle(): void
    {
        try {
            Mail::send(
                "mail.{$this->mailView}",
                $this->mailData,
                function ($message) {
                    $message->to($this->toEmail)
                            ->subject($this->subject);
                }
            );

            Notification::where('id', $this->notificationId)
                ->update(['status' => 'sent', 'sent_at' => now()]);

        } catch (\Throwable $e) {
            // Log the failure but never let an email error crash the HTTP response
            \Log::warning('Email notification failed', [
                'notification_id' => $this->notificationId,
                'to'              => $this->toEmail,
                'view'            => $this->mailView,
                'error'           => $e->getMessage(),
            ]);

            Notification::where('id', $this->notificationId)
                ->update(['status' => 'failed']);
        }
    }

    public function failed(\Throwable $e): void
    {
        Notification::where('id', $this->notificationId)
            ->update(['status' => 'failed']);
    }
}
