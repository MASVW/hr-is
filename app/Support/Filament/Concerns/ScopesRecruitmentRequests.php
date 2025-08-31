<?php

namespace App\Support\Filament\Concerns;

use App\Models\RecruitmentRequest;
use Illuminate\Database\Eloquent\Builder;

trait ScopesRecruitmentRequests
{
    protected static function scopedRequests(): Builder
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        $q = RecruitmentRequest::query();

        if (! $user) {
            return $q->whereRaw('1=0');
        }

        if (
            ($user->isHrDept()
                && ($user->isAssMan()
                    || $user->isDirector()
                    || $user->isManager()
                    || $user->isTeamLeader()))
            || $user->isSU()
        ) {
            return $q;
        }

        if ($user->hasRole(['Staff'])) {
            return $q->where('pic_id', $user->id);
        }

        return $q->where('department_id', $user->department_id);
    }
}
