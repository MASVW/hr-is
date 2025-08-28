<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\RecruitmentStats;
use App\Filament\Widgets\StatusDistributionChart;
use App\Filament\Widgets\LatestRecruitmentTable;
use App\Filament\Resources\RecruitmentRequestResource;
use Filament\Actions;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Dashboard';
}
