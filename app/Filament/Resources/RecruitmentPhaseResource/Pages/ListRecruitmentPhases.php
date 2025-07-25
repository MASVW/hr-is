<?php

namespace App\Filament\Resources\RecruitmentPhaseResource\Pages;

use App\Filament\Resources\RecruitmentPhaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecruitmentPhases extends ListRecords
{
    protected static string $resource = RecruitmentPhaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
