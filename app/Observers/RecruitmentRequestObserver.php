<?php

namespace App\Observers;

use App\Models\RecruitmentRequest;
use App\Models\User;
use App\Observers\Concerns\SendsRecruitmentNotifications;
use App\Support\Notify;

class RecruitmentRequestObserver
{
    use SendsRecruitmentNotifications;

    public function updated(RecruitmentRequest $model): void
    {
        $this->notifyStatusChanged($model, auth()->user());
    }
}
