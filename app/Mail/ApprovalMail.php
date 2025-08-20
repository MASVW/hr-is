<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApprovalMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $subjectLine,
        public string $greeting,
        public string $messageLine,
        public string $approveUrl,
        public string $rejectUrl,
    ) { $this->afterCommit = true; }

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->markdown('mail.approval')
            ->withSymfonyMessage(function ($message) {
                $path = public_path('img/logo_iag.png');
                if (is_file($path)) {
                    $message->embedFromPath($path, 'logo_iag', 'image/png');
                }
            });
    }
}
