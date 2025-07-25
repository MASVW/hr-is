<?php

namespace App\Filament\Resources\RecruitmentRequestResource\Pages;

use App\Filament\Resources\RecruitmentRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListRecruitmentRequests extends ListRecords
{
    protected static string $resource = RecruitmentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
