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
        string   $actorName
    ): void {
        foreach ($recipients as $r) {
            // 1) Isi bel (DB) + update badge realtime
            FNotification::make()
                ->title("Recruitment {$action}")
                ->body(self::buildBody($action, $recruitmentId, $context, $actorName))
                ->success()
                ->sendToDatabase($r, isEventDispatched: true);

            // 2) Toast realtime (broadcast via Reverb → Echo)
            $r->notify(new RecruitmentActivityNotification(
                recruitmentId: $recruitmentId,
                action:        $action,
                performedByName: $actorName,
                performedById:   $actorId,
                context:       $context,
            ));
        }
    }

    private static function buildBody(string $action, string $id, array $ctx, string $by): string
    {
        return match ($action) {
            'status_changed' => sprintf('%s mengubah status #%s: %s → %s', $by, $id, $ctx['from'] ?? '-', $ctx['to'] ?? '-'),
            default          => sprintf('%s memperbarui #%s: %s', $by, $id, $ctx['title'] ?? 'Detail diperbarui'),
        };
    }
}
