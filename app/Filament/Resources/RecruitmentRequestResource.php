<?php

namespace App\Filament\Resources;

use App\Filament\Admin\Resources\RecruitmentRequestResource\Pages;
use App\Filament\Admin\Resources\RecruitmentRequestResource\RelationManagers;
use App\Models\RecruitmentRequest;
use App\Tables\Columns\DetailViewer;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RecruitmentRequestResource extends Resource
{
    protected static ?string $model = RecruitmentRequest::class;
    protected static ?string $navigationGroup = 'Request Management';
    protected static ?string $navigationIcon = 'fluentui-branch-request-20-o';
    protected static ?string $activeNavigationIcon = 'fluentui-branch-request-20';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('requester.name')
                    ->label('Diminta Oleh')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->alignment(Alignment::Center)
                    ->searchable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->searchable()
                    ->alignment(Alignment::Center),
                Tables\Columns\TextColumn::make('pic.name')
                    ->label('Person In Charge')
                    ->badge()
                    ->alignment(Alignment::Center),
                Tables\Columns\TextColumn::make('recruitmentPhase')
                    ->label('Dalam Perkembangan')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $data = $record->recruitmentPhase['form_data']['phases'];
                        $progressPhases = array_filter(
                            $data,
                            fn ($item) => $item['status'] === 'progress'
                        );
                        $names = array_column($progressPhases, 'name');
                        return $names;
                    })->alignment(Alignment::Center)
                    ->tooltip("Tekan untuk melihat detail"),
                Tables\Columns\IconColumn::make('approval.status')
                    ->label('Status Approval')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignment(Alignment::Center)
                    ->tooltip(fn(Model $record):string => ucfirst($record->approval->status))
                    ->getStateUsing(fn ($record) => $record->approval->status === 'approved'),
                Tables\Columns\IconColumn::make('approval.hrd_approval')
                    ->label('Disetujui HR Manager')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignment(Alignment::Center)
                    ->tooltip(fn(Model $record):string => ucfirst($record->approval->hrd_approval))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('approval.chairman_approval')
                    ->label('Disetujui Direksi')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignment(Alignment::Center)
                    ->tooltip(fn(Model $record):string => ucfirst($record->approval->chairman_approval))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordAction('view_phases')
            ->actions([
                Tables\Actions\Action::make('view_phases')
                    ->hiddenLabel()
                    ->icon('heroicon-o-eye')
                    ->modal()
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn($record) => view('livewire.wizard-modal', ['record' => $record->recruitmentPhase]))
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with(['recruitmentPhase', 'recruitmentApproval']);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\RecruitmentRequestResource\Pages\ListRecruitmentRequests::route('/'),
            'create' => \App\Filament\Resources\RecruitmentRequestResource\Pages\CreateRecruitmentRequest::route('/create'),
            'edit' => \App\Filament\Resources\RecruitmentRequestResource\Pages\EditRecruitmentRequest::route('/{record}/edit'),
        ];
    }
}
