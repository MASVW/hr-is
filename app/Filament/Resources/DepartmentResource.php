<?php

namespace App\Filament\Resources;

use App\Filament\Admin\Resources\DepartmentResource\Pages;
use App\Filament\Admin\Resources\DepartmentResource\RelationManagers;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;
    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $navigationIcon = 'bx-group';
    protected static ?string $activeNavigationIcon = 'bxs-group';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hiddenLabel(),
                Tables\Actions\DeleteAction::make()
                    ->hiddenLabel()
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
            'index' => \App\Filament\Resources\DepartmentResource\Pages\ListDepartments::route('/'),
            'create' => \App\Filament\Resources\DepartmentResource\Pages\CreateDepartment::route('/create'),
            'edit' => \App\Filament\Resources\DepartmentResource\Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}
