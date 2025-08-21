<?php

// app/Observers/Concerns/SendsRecruitmentNotifications.php
namespace App\Observers\Concerns;

use App\Models\User;
use App\Support\Notify;

trait SendsRecruitmentNotifications
{
    protected function notifyStatusChanged(object $model, ?object $actor = null): void
    {

    }
}

