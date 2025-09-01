<?php

namespace App\Filament\Resources;

use App\Filament\Admin\Resources\RecruitmentPhaseResource\Pages;
use App\Models\RecruitmentPhase;
use App\Support\AccessHelper;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RecruitmentPhaseResource extends Resource
{
    protected static ?string $model = RecruitmentPhase::class;
    protected static ?string $navigationGroup = 'Request Management';
    protected static ?string $navigationIcon = 'tabler-versions';
    protected static ?string $activeNavigationIcon = 'tabler-versions-filled';

    public static function canAccess(): bool
    {
        return AccessHelper::canAccessGlobal();
    }
    public static function canView(Model $record): bool
    {
        return AccessHelper::canViewHR();
    }
    public static function canEdit(Model $record): bool
    {
        return true;
    }
    public static function canDelete(Model $record): bool
    {
        return false;
    }
    public static function canCreate(): bool
    {
        return false;
    }

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('request.title')
                    ->alignment(Alignment::Left)
                    ->label('Judul Permintaan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('request.department.name')
                    ->alignment(Alignment::Center)
                    ->label('Departemen'),
                Tables\Columns\TextColumn::make('form_data')
                    ->label('Dalam Tahap')
                    ->searchable()
                    ->sortable()
                    ->alignment(Alignment::Center)
                    ->formatStateUsing(function ($record) {
                        $phases = $record->form_data['phases'] ?? [];
                        foreach ($phases as $phase) {
                            if (($phase['status'] ?? null) === 'progress') {
                                return "{$phase['name']}" ?? '-';
                            }
                        }
                        return '-';
                    }),
                Tables\Columns\IconColumn::make('status')
                    ->label('Status Approval')
                    ->alignment(Alignment::Center)
                    ->tooltip(fn ($record) => ucfirst($record->status ?? 'Pending'))
                    ->icon(fn ($record) => match ($record->status) {
                        'approved', 'finish'   => 'heroicon-o-check-circle',
                        'progress'             => 'heroicon-o-clock',
                        'rejected'             => 'heroicon-o-x-circle',
                        default                => 'heroicon-o-clock',
                    })
                    ->color(fn ($record) => match ($record->status) {
                        'approved', 'finish'   => 'success',
                        'progress'             => 'warning',
                        'rejected'             => 'danger',
                        default                => 'gray',
                    }),
                Tables\Columns\TextColumn::make('finishAt')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Dibuat Pada')
                    ->since()
                    ->tooltip(fn($record): string => $record->started_at?->format('d F Y h:i A'))
                    ->alignment(Alignment::Center)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d F Y h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d F Y h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hiddenLabel()
                    ->disabled(fn(Model $record): bool =>
                    collect($record->form_data['phases'] ?? [])
                        ->contains(fn($phase) =>
                            (($phase['status'] ?? null) === 'progress'
                                && in_array(($phase['name'] ?? ''), ['Requesting', 'Approval by Stakeholder'])
                            )
                            || ($phase['status'] ?? null) === 'cancel'
                        )
                    )
            ])
            ->recordUrl(fn(Model $record) => collect($record->form_data['phases'] ?? [])
                ->contains(fn($phase) => ($phase['status'] ?? null) === 'progress' && (($phase['name'] ?? '') === 'Requesting' || ($phase['name'] ?? '') === 'Approval by Stakeholder'))
                ? null // row tidak bisa diklik
                : static::getUrl('edit', ['record' => $record])
            );
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['recruitmentRequest.department', 'recruitmentRequest.approval']);

        if (auth()->user()->isStaff()) {
            $query
                ->whereHas(
            'recruitmentRequest.approval', fn($q) => $q->where('status', 'approved'))
                ->whereHas('recruitmentRequest', fn($q) => $q->where('pic_id', auth()->id()));;
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\RecruitmentPhaseResource\Pages\ListRecruitmentPhases::route('/'),
            'create' => \App\Filament\Resources\RecruitmentPhaseResource\Pages\CreateRecruitmentPhase::route('/create'),
            'edit' => \App\Filament\Resources\RecruitmentPhaseResource\Pages\EditRecruitmentPhase::route('/{record}/edit'),
        ];
    }
}
