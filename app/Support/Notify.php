<?php

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
        string   $action,                  // 'detail_change' | 'phase_status_changed' | 'status_changed' | ...
        array    $context,                 // untuk 'detail_change': list of changes; lainnya: assoc (from/to/...)
        string   $actorId,
        string   $actorName,
        ?string  $department
    ): void {
        foreach ($recipients as $r) {
            FNotification::make()
                ->title(self::buildHead($action, $department))
                ->body(self::buildBody($action, $recruitmentId, $context, $actorName, $department))
                ->success()
                ->sendToDatabase($r, isEventDispatched: true);

            $r->notify(new RecruitmentActivityNotification(
                recruitmentId:     $recruitmentId,
                action:            $action,
                performedByName:   $actorName,
                performedById:     $actorId,
                department:        $department ?? null,
                context:           $context,
            ));
        }
    }

    private static function buildHead(string $action, ?string $department): string
    {
        $dept = $department ?? '-';
        return match ($action) {
            'phase_status_changed' => sprintf('Pembaharuan Status Tahap Perekrutan Departemen %s', $dept),
            'detail_change'        => sprintf('Pembaharuan Detail Tahap Perekrutan Departemen %s', $dept),
            default                => sprintf('Pembaharuan Perekrutan Departemen %s', $dept),
        };
    }

    private static function buildBody(
        string $action,
        string $id,
        array $ctx,
        string $by,
        ?string $department
    ): string {
        $dept = $department ?? '-';

        // Helper untuk cetak nilai secara aman
        $val = static function ($v): string {
            if ($v === null) return '-';
            if (is_bool($v)) return $v ? 'true' : 'false';
            if (is_array($v)) return json_encode($v, JSON_UNESCAPED_UNICODE);
            return (string) $v;
        };

        return match ($action) {
            'status_changed' => sprintf(
                '%s mengubah status #%s: %s → %s',
                $by,
                $id,
                $val($ctx['from'] ?? null),
                $val($ctx['to']   ?? null),
            ),

            'phase_status_changed' => sprintf(
                '%s telah memperbarui status tahap "%s" departemen %s dari %s menjadi %s',
                $by,
                $ctx['phase'] ?? null,
                $dept,
                $val($ctx['from'] ?? null),
                $val($ctx['to']   ?? null),
            ),

            'detail_change' => (static function () use ($by, $ctx, $val, $dept) {
                // ctx bisa berupa list of changes ATAU assoc tunggal
                if (empty($ctx)) {
                    return sprintf('%s memperbarui detail (tanpa rincian perubahan).', $by);
                }

                // Jika assoc tunggal: ubah jadi list 1 elemen
                if (isset($ctx['field']) || isset($ctx['phase'])) {
                    $ctx = [ $ctx ];
                }

                $first = $ctx[0] ?? [];
                $total = is_countable($ctx) ? max(1, count($ctx)) : 1;

                $field = $first['field'] ?? 'field';
                $phase = $first['phase'] ?? '-';
                $from  = $val($first['oldValue'] ?? null);
                $to    = $val($first['newValue'] ?? null);

                $suffix = $total > 1 ? sprintf(' (+%d perubahan lainnya)', $total - 1) : '';

                if ($field === "reviseNotes") {
                    return sprintf(
                        '%s melakukan pengunduran ke tahap "%s" pada permintaan perekrutan %s',
                        $by,
                        $phase,
                        $dept
                    );
                }

                return sprintf(
                    '%s memperbarui field "%s" pada tahap %s dari %s menjadi %s%s.',
                    $by,
                    $field,
                    $phase,
                    $from,
                    $to,
                    $suffix
                );
            })(),

            default => sprintf(
                '%s memperbarui #%s: %s',
                $by,
                $id,
                $val($ctx['title'] ?? 'Detail diperbarui')
            ),
        };
    }
}
