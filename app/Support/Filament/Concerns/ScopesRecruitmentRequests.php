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

        if ($user->isAssMan() || $user->isDirector() || $user->isManager() || $user->isSU() ) {
            return $q;
        }

        if ($user->hasRole(['Team Leader', 'Staff'])) {
            return $q->where('department_id', $user->department_id);
        }

        return $q->where('department_id', $user->department_id);
    }
}
