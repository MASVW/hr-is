<?php

namespace App\Filament\Widgets;

use App\Support\Filament\Concerns\ScopesRecruitmentRequests;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RecruitmentStats extends BaseWidget
{
    use ScopesRecruitmentRequests;
    protected function getStats(): array
    {
        $base = self::scopedRequests();

        $pending  = (clone $base)->where('status', 'pending')->count();
        $progress = (clone $base)->where('status', 'progress')->count();
        $finish   = (clone $base)->where('status', 'finish')->count();
        $total    = (clone $base)->count();

        return [
            Stat::make('Pending', $pending)->color('danger'),
            Stat::make('Progress', $progress)->color('warning'),
            Stat::make('Finish', $finish)->color('success'),
            Stat::make('Total', $total)->color('primary'),
        ];
    }
}
