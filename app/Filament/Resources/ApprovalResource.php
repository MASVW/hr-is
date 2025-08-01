<?php

namespace App\Filament\Resources;

use App\Filament\Admin\Resources\ApprovalResource\Pages;
use App\Filament\Admin\Resources\ApprovalResource\RelationManagers;
use App\Models\Approval;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ApprovalResource extends Resource
{
    protected static ?string $model = Approval::class;
    protected static ?string $navigationIcon = "heroicon-o-check-circle";
    protected static ?string $activeNavigationIcon = "heroicon-s-check-circle";
    protected static ?string $navigationGroup = 'Request Management';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->searchable(),
                Tables\Columns\IconColumn::make('hrd_approval')
                    ->label('Approval By HRD')
                    ->alignment(Alignment::Center)
                    ->boolean(),
                Tables\Columns\IconColumn::make('chairman_approval')
                    ->label('Approval By Direction')
                    ->alignment(Alignment::Center)
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_closed')
                    ->label('Closed')
                    ->alignment(Alignment::Center)
                    ->boolean(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->since()
                    ->label('Approved At')
                    ->tooltip(fn($record):string => $record['approved_at'] ? (date_format($record['approved_at'], 'd F Y h:i A')) : '')
                    ->alignment(Alignment::Center)
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('request.status')
                    ->trueIcon('heroicon-o-check-circle')
                    ->trueColor('success')
                    ->falseIcon('heroicon-o-x-circle')
                    ->falseColor('danger')
                    ->label('Request Status')
                    ->alignment(Alignment::Center)
                    ->searchable(),
            ])
            ->filters([
                //
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with([
            'request'
        ]);
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
            'index' => \App\Filament\Resources\ApprovalResource\Pages\ListApprovals::route('/'),
            'create' => \App\Filament\Resources\ApprovalResource\Pages\CreateApproval::route('/create'),
            'edit' => \App\Filament\Resources\ApprovalResource\Pages\EditApproval::route('/{record}/edit'),
        ];
    }
}
