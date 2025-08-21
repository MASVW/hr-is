<?php

namespace App\Support;

use App\Mail\ApprovalMail;
use App\Mail\NotificationMail;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;

class Emailer
{
    public static function notify(
        User|array|string $to,
        string $subject,
        string $message,
        ?string $actionText = null,
        ?string $actionUrl = null
    ): void {
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
     * Kirim email approval.
     * - recruitmentId diambil dari $approvable->getRouteKey() bila tidak diberikan.
     * - userId diambil dari $approver (jika instance User) bila tidak diberikan.
     */
    public static function approval(
        User|array|string $approver,
        string $type,            // gunakan bila Mailable/DB butuh; kalau tidak, hilangkan saja
        Model $approvable,
        string $subject,
        string $message,
        array $context,
        ?string $recruitmentId = null,
        ?string $userId = null,
        ?int $expiresIn = null,
    ): void {
        // Derive recruitmentId dari model jika kosong
        $derivedRecruitmentId = $recruitmentId ?: (string) ($approvable->getRouteKey() ?? $approvable->getKey());

        // Derive userId dari approver kalau dia instance User
        $derivedUserId = $userId;
        if ($derivedUserId === null && $approver instanceof User) {
            $derivedUserId = (string) ($approver->getRouteKey() ?? $approver->getKey());
        }

        if ($derivedUserId === null || $derivedUserId === '') {
            throw new InvalidArgumentException('userId wajib diisi jika $approver bukan instance User.');
        }

        $approveUrl = self::generateApprovalUrl('approvals.approve', $derivedRecruitmentId, $derivedUserId, $expiresIn);
        $rejectUrl  = self::generateApprovalUrl('approvals.reject',  $derivedRecruitmentId, $derivedUserId, $expiresIn);

        Mail::to($approver)->queue(new ApprovalMail(
            subjectLine: $subject,
            greeting: __('emails.greeting', [], 'id'),
            messageLine: $message,
            // Kalau ingin memakai $context di template, tambahkan properti/argumen di ApprovalMail
            // context: $context,
            approveUrl: $approveUrl,
            rejectUrl:  $rejectUrl,
        ));
    }

    private static function generateApprovalUrl(string $route, string $recruitmentId, string $userId, ?int $expiresIn): string
    {
        $params = ['recruitmentId' => $recruitmentId, 'userId' => $userId];

        return $expiresIn
            ? URL::temporarySignedRoute($route, now()->addMinutes($expiresIn), $params)
            : URL::signedRoute($route, $params);
    }
}
