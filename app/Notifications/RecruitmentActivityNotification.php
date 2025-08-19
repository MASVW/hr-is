<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecruitmentActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $recruitmentId,
        public string $action,
        public string $performedByName,
        public string $performedById,
        public ?string $department,
        public array  $context = [],
        public bool   $sendMail = false,
    ) {
        $this->afterCommit = true;
    }

    public function via($notifiable): array
    {
        $channels = ['broadcast'];
        if ($this->sendMail && filled($notifiable->email)) {
            $channels[] = 'mail';
        };

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('filament.admin.resources.recruitment-phases.edit', $this->recruitmentId);

        $subject = match ($this->action) {
            'status_changed'      => "Status Rekrutmen Diperbarui #{$this->recruitmentId}",
            'phase_status_changed'=> "Pembaharuan Status Tahap Perekrutan Departemen {$this->department}",
            default               => "Rekrutmen Diperbarui #{$this->recruitmentId}",
        };


        $line = match ($this->action) {
            'status_changed' => "{$this->performedByName} mengubah status #{$this->recruitmentId}: ".
                ($this->context['from'] ?? '-') . " → " . ($this->context['to'] ?? '-'),
            'phase_status_changed' => "{$this->performedByName} telah memperbarui status tahap perekrutan departemen ".
                ($this->department)." dari {$this->context['from']} menjadi {$this->context['to']}",
            default          => "{$this->performedByName} memperbarui #{$this->recruitmentId}: ".
                ($this->context['title'] ?? 'Detail diperbarui'),
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Halo,')
            ->line($line)
            ->action('Lihat di HRIS', $url)
            ->salutation('Terima kasih');
    }

    public function broadcastType(): string
    {
        return 'recruitment.activity';
    }

    public function toArray($notifiable): array
    {
        return [
            'recruitment_id' => $this->recruitmentId,
            'action'         => $this->action,
            'by'             => ['id' => $this->performedById, 'name' => $this->performedByName],
            'context'        => $this->context,
            'at'             => now()->toISOString(),
            'title'          => 'Recruitment ' . $this->action,
            'body'           => $this->buildBody(),
            'status'         => $this->mapStatus(),
        ];
    }

    private function buildBody(): string
    {
        return match ($this->action) {
            'status_changed' => sprintf(
                '%s mengubah status #%s: %s → %s',
                $this->performedByName,
                $this->recruitmentId,
                $this->context['from'] ?? '-',
                $this->context['to'] ?? '-',
            ),
            'phase_status_changed' => sprintf(
                '%s telah memperbarui status tahap perekrutan departemen %s dari %s menjadi %s',
                $this->performedByName,
                $this->department,
                $this->context['from'] ?? '-',
                $this->context['to'] ?? '-',
            ),
            // ⬇️ Tambahan ini
            'detail_change' => (function () {
                $ctx = $this->context;
                if (isset($ctx['field']) || isset($ctx['phase'])) {
                    $ctx = [$ctx];
                } else {
                    $ctx = array_values($ctx ?? []);
                }

                foreach ($ctx as $c) {
                    if (($c['field'] ?? null) === 'reviseNotes') {
                        $phase = $c['phase'] ?? '-';
                        return sprintf(
                            '%s melakukan pengunduran ke tahap "%s" pada permintaan perekrutan %s',
                            $this->performedByName,
                            $phase,
                            $this->department ?? '-'
                        );
                    }
                }

                // Fallback ringkas bila tidak ada 'reviseNotes'
                $first = $ctx[0] ?? [];
                return sprintf(
                    '%s memperbarui field "%s" pada tahap %s',
                    $this->performedByName,
                    $first['field'] ?? 'field',
                    $first['phase'] ?? '-'
                );
            })(),
            default => sprintf(
                '%s melakukan %s pada #%s',
                $this->performedByName, $this->action, $this->recruitmentId
            ),
        };
    }


    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function viaQueues(): array
    {
        return ['broadcast' => 'broadcasts'];
    }

    public function viaConnections(): array
    {
        return ['broadcast' => 'redis'];
    }

    private function mapStatus(): string
    {
        return $this->action === 'status_changed' ? 'Success' : 'Info';
    }
}
