<?php

// app/Observers/RecruitmentPhaseObserver.php
namespace App\Observers;

use App\Models\RecruitmentPhase;
use App\Observers\Concerns\SendsRecruitmentNotifications;

class RecruitmentPhaseObserver
{
    use SendsRecruitmentNotifications;

    public function updated(RecruitmentPhase $model): void
    {
        $this->notifyStatusChanged($model, auth()->user());
    }
}
