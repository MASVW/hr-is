<?php

namespace App\Support;

use App\Mail\ApprovalMail;
use App\Mail\NotificationMail;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
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
        string $type,
        Model $approvable,
        string $subject,
        string $message,
        array $context,
        ?string $recruitmentId = null,
        ?string $userId = null,
        ?int $expiresIn = null,
    ): void {
        // Ambil ID request & user
        $derivedRecruitmentId = $recruitmentId ?: (string) ($approvable->getRouteKey() ?? $approvable->getKey());

        $derivedUserId = $userId;
        if ($derivedUserId === null && $approver instanceof User) {
            $derivedUserId = (string) ($approver->getRouteKey() ?? $approver->getKey());
        }
        if ($derivedUserId === null || $derivedUserId === '') {
            throw new \InvalidArgumentException('userId wajib diisi jika $approver bukan instance User.');
        }

        // Pilih route sesuai role & tipe
        $isRecruitment = $approvable instanceof \App\Models\RecruitmentRequest;
        $isPenambahan  = $isRecruitment && strtolower((string)$approvable->recruitment_type) === 'penambahan';
        $isDirutRecip  = $approver instanceof User && $approver->hasRole('Dirut');

        if ($isPenambahan && $isDirutRecip) {
            $routeApprove = 'approvals.dirut.approve';
            $routeReject  = 'approvals.dirut.reject';
        } else {
            $routeApprove = 'approvals.approve';
            $routeReject  = 'approvals.reject';
        }

        // SIGN RELATIVE -> jadikan ABSOLUTE (sesuai pola lama, valid untuk signed:relative)
        $approveUrl = self::generateApprovalUrl($routeApprove, $derivedRecruitmentId, $derivedUserId, $expiresIn);
        $rejectUrl  = self::generateApprovalUrl($routeReject,  $derivedRecruitmentId, $derivedUserId, $expiresIn);

        $expiresAt = $expiresIn
            ? Carbon::now('Asia/Jakarta')->addMinutes($expiresIn)->format('d M Y H:i') . ' WIB'
            : null;

        Mail::to($approver)->queue(new ApprovalMail(
            subjectLine: $subject,
            greeting: __('emails.greeting', [], 'id'),
            messageLine: $message,
            approveUrl:  $approveUrl,
            rejectUrl:   $rejectUrl,
            context:     $context,
            expiresAt:   $expiresAt
        ));
    }

    private static function generateApprovalUrl(string $route, string $recruitmentId, string $userId, ?int $expiresIn): string
    {
        $params = ['recruitmentId' => $recruitmentId, 'userId' => $userId];

        $relative = $expiresIn
            ? URL::temporarySignedRoute($route, now()->addMinutes($expiresIn), $params, absolute: false)
            : URL::signedRoute($route, $params, absolute: false);

        return URL::to($relative);
    }
}
