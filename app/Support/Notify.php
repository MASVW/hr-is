<?php

// app/Support/Notify.php
namespace App\Support;

use Filament\Notifications\Notification as FNotification;
use App\Notifications\RecruitmentActivityNotification;
use App\Models\User;

class Notify
{
    /**
     * Kirim notifikasi Filament (bel) + broadcast (toast) ke banyak user.
     */
    public static function recruitmentActivity(
        iterable $recipients,              // Collection<User>|User[]
        string   $recruitmentId,
        string   $action,                  // 'updated' | 'status_changed' | ...
        array    $context,                 // ['title'=>..., 'from'=>..., 'to'=>...]
        string   $actorId,
        string   $actorName,
        ?string   $department
    ): void {
        dd($context);
        foreach ($recipients as $r) {
            FNotification::make()
                ->title(self::buildHead($action, $department))
                ->body(self::buildBody($action, $recruitmentId, $context, $actorName, $department))
                ->success()
                ->sendToDatabase($r, isEventDispatched: true);

            $r->notify(new RecruitmentActivityNotification(
                recruitmentId: $recruitmentId,
                action:        $action,
                performedByName: $actorName,
                performedById:   $actorId,
                department:     $department || null,
                context:       $context,
            ));
        }
    }

    private static function buildHead(
        string $action,
        ?string $department
    ): string
    {
        return match ($action){
            'phase_status_changed'=> sprintf(
                'Pembaharuan Status Tahap Perekrutan Departemen  %s',
                $department
            ),
            'detail_change'=> sprintf(
                'Pembaharuan Detail Tahap Perekrutan Departemen  %s',
                $department
            ),
        };
    }

    private static function buildBody(
        string $action,
        string $id,
        array $ctx,
        string $by,
        ?string $department
    ): string
    {
        return match ($action) {
            'status_changed' => sprintf('%s mengubah status #%s: %s → %s', $by, $id, $ctx['from'] ?? '-', $ctx['to'] ?? '-'),
            'phase_status_changed' => sprintf(
                '%s telah memperbarui status tahap perekrutan departemen %s dari %s menjadi %s',
                $by,
                $department,
                $ctx['from'],
                $ctx['to'],
            ),
            'detail_change' => sprintf('%s memperbarui field "%s" pada tahap %s dari %s menjadi %s.',
                $by,
                ucfirst($ctx[0]['field']),
                ucfirst($ctx[0]['phase']),
                ucfirst($ctx[0]['oldValue']),
                ucfirst($ctx[0]['newValue']),

            ),
            default          => sprintf('%s memperbarui #%s: %s', $by, $id, $ctx['title'] ?? 'Detail diperbarui'),
        };
    }
}
