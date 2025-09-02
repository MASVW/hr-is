<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Filament\Resources\LocationResource\RelationManagers;
use App\Models\Location;
use App\Support\AccessHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LocationResource extends Resource
{
    protected static ?string $navigationIcon = "heroicon-o-map-pin";
    protected static ?string $activeNavigationIcon = "heroicon-s-map-pin";
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 5;
    public static function canAccess(): bool
    {
        return AccessHelper::canAccessHR();
    }
    public static function canView(Model $record): bool
    {
        return AccessHelper::canViewHR();
    }
    public static function canCreate(): bool
    {
        return AccessHelper::canCreateHR();
    }
    public static function canEdit(Model $record): bool
    {
        return AccessHelper::canEditHR();
    }
    public static function canDelete(Model $record): bool
    {
        return AccessHelper::canDeleteHR();
    }

    protected static ?string $model = Location::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label("Nama Kantor")
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->label("Lokasi Kantor")
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diupdate')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hiddenLabel(),
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
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
}
