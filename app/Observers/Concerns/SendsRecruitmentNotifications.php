<?php

// app/Observers/Concerns/SendsRecruitmentNotifications.php
namespace App\Observers\Concerns;

use App\Models\User;
use App\Support\Notify;

trait SendsRecruitmentNotifications
{
    protected function notifyStatusChanged(object $model, ?object $actor = null): void
    {
        if (! method_exists($model, 'wasChanged') || ! $model->wasChanged('status')) {
            return;
        }

        $old = $model->getOriginal('status');
        $new = $model->status;

        $actorId   = (string) ($actor->id ?? 'system');
        $actorName = $actor->name ?? 'System';

        $recipients = User::role(['hr', 'stakeholder'])
            ->when($actor, fn ($q) => $q->whereKeyNot($actor->getKey())) // opsional: exclude actor
            ->get();

        Notify::recruitmentActivity(
            recipients:    $recipients,
            recruitmentId: (string) $model->getKey(),
            action:        'status_changed',
            context:       ['from' => $old, 'to' => $new, 'title' => $model->title ?? ''],
            actorId:       $actorId,
            actorName:     $actorName,
        );
    }
}

