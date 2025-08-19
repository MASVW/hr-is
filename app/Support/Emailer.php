<?php

namespace App\Support;

use App\Mail\ApprovalMail;
use App\Mail\NotificationMail;
use App\Models\Approval;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class Emailer
{
    /**
     * Kirim email notifikasi umum.
     */
    public static function notify(
        User|array|string $to,
        string $subject,
        string $message,
        ?string $actionText = null,
        ?string $actionUrl = null): void
    {
        Mail::to($to)->queue(new NotificationMail(
            subjectLine: $subject,
            greeting: __('emails.greeting', [], 'id'),
            footer: __('emails.thanks', [], 'id'),
            messageLine: $message,
            actionText: $actionText,
            actionUrl: $actionUrl,
        ));
    }

    /**
     * Buat approval request + kirim email dengan tombol (approve/reject).
     */
    public static function approval(
        User|array|string $approver,
        string $type,
        Model $approvable,
        string $subject,
        string $message,
        array $context,
        string $recruitmentId,
        string $userId,
        ?int $expiresIn = null,
    ): void {
        $approveUrl = self::generateApprovalUrl('approvals.approve', $recruitmentId, $userId, $expiresIn);
        $rejectUrl  = self::generateApprovalUrl('approvals.reject', $recruitmentId, $userId, $expiresIn);

        Mail::to($approver)->queue(new ApprovalMail(
            subjectLine: $subject,
            greeting: __('emails.greeting', [], 'id'),
            messageLine: $message,
            approveUrl: $approveUrl,
            rejectUrl:  $rejectUrl,
        ));
    }

    /**
     * Generate approval/reject URL (signed/temporary).
     */
    private static function generateApprovalUrl(string $route, string $recruitmentId, string $userId, ?int $expiresIn): string
    {
        $params = ['recruitmentId' => $recruitmentId, 'userId' => $userId];

        return $expiresIn
            ? URL::temporarySignedRoute($route, now()->addMinutes($expiresIn), $params)
            : URL::signedRoute($route, $params);
    }
}
