<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApprovalMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string  $subjectLine,
        public string  $greeting,
        public string  $messageLine,
        public string  $approveUrl,
        public string  $rejectUrl,
        public array   $context = [],     // <<— baru
        public ?string $expiresAt = null, // <<— baru
    ) {
        $this->afterCommit = true;
    }

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->markdown('mail.approval')
            ->with([
                'greeting'   => $this->greeting,
                'messageLine'=> $this->messageLine,
                'approveUrl' => $this->approveUrl,
                'rejectUrl'  => $this->rejectUrl,
                'ctx'        => $this->context,
                'expiresAt'  => $this->expiresAt,
            ])
            ->withSymfonyMessage(function ($message) {
                // Sematkan logo sebagai CID: logo_iag
                $path = public_path('img/logo_iag.png');
                if (is_file($path)) {
                    $message->embedFromPath($path, 'logo_iag', 'image/png');
                }
            });
    }
}
