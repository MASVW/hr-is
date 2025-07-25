<?php

namespace App\Filament\Resources;

use App\Filament\Admin\Resources\ApprovalResource\Pages;
use App\Filament\Admin\Resources\ApprovalResource\RelationManagers;
use App\Models\Approval;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ApprovalResource extends Resource
{
    protected static ?string $model = Approval::class;
    protected static ?string $navigationIcon = "heroicon-o-check-circle";
    protected static ?string $activeNavigationIcon = "heroicon-s-check-circle";
    protected static ?string $navigationGroup = 'Request Management';



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->searchable(),
                Tables\Columns\IconColumn::make('hrd_approval')
                    ->label('Approval By HRD')
                    ->boolean(),
                Tables\Columns\IconColumn::make('chairman_approval')
                    ->label('Approval By Direction')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_closed')
                    ->label('Closed')
                    ->boolean(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved At')
                    ->dateTime()
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
                Tables\Columns\TextColumn::make('request.status')
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->label('Request Status')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
