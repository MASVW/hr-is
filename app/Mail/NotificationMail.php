<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $subjectLine,
        public string $greeting = 'Halo,',
        public string $messageLine = '',
        public ?string $actionText = null,
        public ?string $actionUrl = null,
    ) { $this->afterCommit = true; }

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->markdown('mail.notification');
    }
}
