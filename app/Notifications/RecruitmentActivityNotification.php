<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class RecruitmentActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $recruitmentId,
        public string $action,         // 'updated' | 'status_changed'
        public string $performedByName,
        public string $performedById,
        public array  $context = []    // ['from'=>'progress','to'=>'finish','title'=>'...']
    ) {
        // gunakan properti dari trait Queueable (JANGAN deklar ulang)
        $this->afterCommit = true;
    }

    public function via($notifiable): array
    {
        // broadcast saja (DB notif pakai Filament Notification)
        return ['broadcast'];
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

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function viaQueues(): array
    {
        return ['broadcast' => 'broadcasts']; // optional: queue khusus
    }

    public function viaConnections(): array
    {
        return ['broadcast' => 'redis'];      // optional: koneksi queue
    }

    public function broadcastType(): string
    {
        return 'recruitment.activity';
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
            'updated' => sprintf(
                '%s memperbarui #%s: %s',
                $this->performedByName,
                $this->recruitmentId,
                $this->context['title'] ?? 'Detail diperbarui',
            ),
            default => sprintf('%s melakukan %s pada #%s', $this->performedByName, $this->action, $this->recruitmentId),
        };
    }

    private function mapStatus(): string
    {
        return $this->action === 'status_changed' ? 'success' : 'info';
    }
}
