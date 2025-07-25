<?php

namespace App\Filament\Resources\RecruitmentPhaseResource\Pages;

use App\Filament\Resources\RecruitmentPhaseResource;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditRecruitmentPhase extends EditRecord
{
    protected static string $resource = RecruitmentPhaseResource::class;

    public function form(Form $form): Form
    {
        $statusOption = [
            "finish" => "Finished",
            "progress" => "On Progress",
            "pending" => "Pending",
        ];
        return $form
            ->schema([
                TextInput::make('status')
                    ->disabled()
                    ->dehydrated(false)
                    ->afterStateHydrated(function($component){
                        $component->state(fn($state) => ucfirst($state));
                    })
                    ->required()
                    ->maxLength(255),
                DateTimePicker::make('started_at')
                    ->disabled()
                    ->dehydrated(false),

                Section::make('Form Data')
                    ->description(function (){
                        $phases = $this->record->form_data['phases'] ?? [];
                        foreach ($phases as $phase) {
                            if (($phase['status'] ?? null) === 'progress') {
                                return "Dalam Tahap {$phase['name']}" ?? '-';
                            }
                        }
                        return '-';
                    })
                    ->statePath('form_data')
                    ->schema([
                        Tabs::make('Phases')
                            ->tabs([
                                Tabs\Tab::make('CV Collection')
                                    ->statePath('phases.2')
                                    ->icon('heroicon-o-document-text')
                                    ->badge(fn ($record) => ucfirst($record->form_data['phases'][2]['status'] ?? ''))
                                    ->badgeColor(fn ($record) => match ($record->form_data['phases'][2]['status'] ?? null) {
                                        'progress' => 'success',
                                        'finish' => 'primary',
                                        default => 'gray'
                                    })
                                    ->schema([
                                        Select::make('status')
                                            ->reactive()

                                            ->options($statusOption),
                                        TextInput::make('totalCV')
                                            ->label('Curriculum Vitae diterima')
                                            ->numeric(),
                                        Textarea::make('note')
                                    ])
                                    ->disabled(function ($record, $state) {
                                        return $record->form_data['phases'][2]['status'] !== 'progress';
                                    })
                                    ->dehydrated(true),

                                Tabs\Tab::make('CV Screening')
                                    ->statePath('phases.3')
                                    ->icon('heroicon-o-magnifying-glass')
                                    ->badge(fn ($record) => ucfirst($record->form_data['phases'][3]['status'] ?? ''))
                                    ->badgeColor(fn ($record) => match ($record->form_data['phases'][3]['status'] ?? null) {
                                        'progress' => 'success',
                                        'finish' => 'primary',
                                        default => 'gray'
                                    })
                                    ->schema([
                                        Select::make('status')
                                            ->reactive()
                                            ->options($statusOption),
                                        TextInput::make('approvedCV')
                                            ->label('Curriculum Vitae Diterima')
                                            ->numeric(),
                                        Textarea::make('note')
                                ])
                                    ->disabled(function ($record) {
                                        return $record->form_data['phases'][3]['status'] !== 'progress';
                                    })
                                    ->dehydrated(true),

                                Tabs\Tab::make('Psychology Assessment')
                                    ->statePath('phases.4')
                                    ->icon('heroicon-o-clipboard-document-check')
                                    ->badge(fn ($record) => ucfirst($record->form_data['phases'][4]['status'] ?? ''))
                                    ->badgeColor(fn ($record) => match ($record->form_data['phases'][4]['status'] ?? null) {
                                        'progress' => 'success',
                                        'finish' => 'primary',
                                        default => 'gray'
                                    })
                                    ->schema([
                                        Select::make('status')
                                            ->reactive()
                                            ->options($statusOption),
                                        TextInput::make('candidate')
                                            ->numeric(),
                                        TextInput::make('finished')
                                            ->numeric(),
                                        TextInput::make('passed')
                                            ->numeric(),
                                        Textarea::make('note'),
                                ])
                                    ->disabled(function ($record, $state) {
                                        return $record->form_data['phases'][4]['status'] !== 'progress';
                                    })->dehydrated(true),

                                Tabs\Tab::make('HRD Interview')
                                    ->statePath('phases.5')
                                    ->icon('heroicon-o-chat-bubble-left-right')
                                    ->badge(fn ($record) => ucfirst($record->form_data['phases'][5]['status'] ?? ''))
                                    ->badgeColor(fn ($record) => match ($record->form_data['phases'][5]['status'] ?? null) {
                                        'progress' => 'success',
                                        'finish' => 'primary',
                                        default => 'gray'
                                    })
                                    ->schema([
                                        Select::make('status')
                                            ->reactive()
                                            ->options($statusOption),
                                        TextInput::make('interviewed')
                                            ->numeric(),
                                        TextInput::make('candidate')
                                            ->numeric(),
                                        TextInput::make('passed')
                                            ->numeric(),
                                        Textarea::make('note'),
                                ])
                                    ->disabled(function ($record) {
                                        return $record->form_data['phases'][5]['status'] !== 'progress';
                                    })
                                    ->dehydrated(true),

                                Tabs\Tab::make('Check Background')
                                    ->statePath('phases.6')
                                    ->icon('heroicon-o-shield-check')
                                    ->badge(fn ($record) => ucfirst($record->form_data['phases'][6]['status'] ?? ''))
                                    ->badgeColor(fn ($record) => match ($record->form_data['phases'][6]['status'] ?? null) {
                                        'progress' => 'success',
                                        'finish' => 'primary',
                                        default => 'gray'
                                    })
                                    ->schema([
                                        Select::make('status')
                                            ->reactive()
                                            ->options($statusOption),
                                        TextInput::make('candidate')
                                            ->numeric(),
                                        TextInput::make('checked')
                                            ->numeric(),
                                        TextInput::make('passed')
                                            ->numeric(),
                                        Textarea::make('note'),
                                ])
                                    ->disabled(function ($record) {
                                        return $record->form_data['phases'][6]['status'] !== 'progress';
                                    })
                                    ->dehydrated(true),

                                Tabs\Tab::make('Interview with User')
                                    ->statePath('phases.7')
                                    ->icon('heroicon-o-user-group')
                                    ->badge(fn ($record) => ucfirst($record->form_data['phases'][7]['status'] ?? ''))
                                    ->badgeColor(fn ($record) => match ($record->form_data['phases'][7]['status'] ?? null) {
                                        'progress' => 'success',
                                        'finish' => 'primary',
                                        default => 'gray'
                                    })
                                    ->schema([
                                        Select::make('status')
                                            ->reactive()
                                            ->options($statusOption),
                                        TextInput::make('interviewed')
                                            ->numeric(),
                                        TextInput::make('candidate')
                                            ->numeric(),
                                        TextInput::make('passed')
                                            ->numeric(),
                                        Textarea::make('note'),
                                ])
                                    ->disabled(function ($record) {
                                        return $record->form_data['phases'][7]['status'] !== 'progress';
                                    })
                                    ->dehydrated(true),

                                Tabs\Tab::make('Offering')
                                    ->statePath('phases.8')
                                    ->icon('heroicon-o-briefcase')
                                    ->badge(fn ($record) => ucfirst($record->form_data['phases'][8]['status'] ?? ''))
                                    ->badgeColor(fn ($record) => match ($record->form_data['phases'][8]['status'] ?? null) {
                                        'progress' => 'success',
                                        'finish' => 'primary',
                                        default => 'gray'
                                    })
                                    ->schema([
                                        Select::make('status')
                                            ->reactive()
                                            ->options($statusOption),
                                        TextInput::make('candidate')
                                            ->numeric(),
                                        TextInput::make('offered')
                                            ->numeric(),
                                        TextInput::make('agreed')
                                            ->numeric(),
                                        Textarea::make('note'),
                                ])
                                    ->disabled(function ($record) {
                                        return $record->form_data['phases'][8]['status'] !== 'progress';
                                    })
                                    ->dehydrated(true),

                                Tabs\Tab::make('Onboarding')
                                    ->statePath('phases.9')
                                    ->icon('heroicon-o-rocket-launch')
                                    ->badge(fn ($record) => ucfirst($record->form_data['phases'][9]['status'] ?? ''))
                                    ->badgeColor(fn ($record) => match ($record->form_data['phases'][9]['status'] ?? null) {
                                        'progress' => 'success',
                                        'finish' => 'primary',
                                        default => 'gray'
                                    })
                                    ->schema([
                                        Select::make('status')
                                            ->reactive()
                                            ->options($statusOption),
                                        TextInput::make('onboarded')
                                            ->numeric(),
                                        Textarea::make('note'),
                                ])
                                    ->disabled(function ($record) {
                                        return $record->form_data['phases'][9]['status'] !== 'progress';
                                    })
                                    ->dehydrated(true),
                                ])
                            ])
                    ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $processData = $data['form_data']['phases'];
        foreach ($processData as $index => $editData){
            if ($editData['status'] === 'finish'){
                $target = $index + 1;
                if ($processData[$target]['status'] === 'pending'){
                    $processData[$target]['status'] = 'progress';
                }
            }
            if ($editData['status'] === 'pending'){
                $target = $index - 1;
                if ($processData[$target]['status'] === 'finish'){
                    $processData[$index]['status'] = 'progress';
                }
            }
        }
        $data['form_data']['phases'] = $processData;
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);
        $record->save();

        logger($record->form_data);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
