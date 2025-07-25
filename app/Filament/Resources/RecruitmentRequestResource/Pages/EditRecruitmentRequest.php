<?php

namespace App\Filament\Resources\RecruitmentRequestResource\Pages;

use App\Filament\Resources\RecruitmentRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecruitmentRequest extends EditRecord
{
    protected static string $resource = RecruitmentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
