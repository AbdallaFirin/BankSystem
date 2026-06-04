<?php

namespace App\Mail;

use App\Models\Staff;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $acceptUrl;

    public function __construct(
        public Staff $staff,
        public string $token,
    ) {
        $this->acceptUrl = route('staff.accept-invite', ['token' => $token]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You\'ve been invited to Gobaad Bank Staff Portal',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.staff-invite',
        );
    }
}
