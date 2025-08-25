<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $activeNavigationIcon = 'heroicon-s-user';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->email()->required()->unique(ignoreRecord: true)->maxLength(255),

            // Password: required saat create, optional saat edit. Di-hash hanya jika diisi.
            Forms\Components\TextInput::make('password')
                ->label('Password')
                ->password()->revealable()->maxLength(255)
                ->required(fn (string $context) => $context === 'create')
                ->rule('min:8')
                ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                ->dehydrated(fn ($state) => filled($state)),

            Forms\Components\TextInput::make('password_confirmation')
                ->label('Konfirmasi Password')
                ->password()->revealable()
                ->dehydrated(false)
                ->requiredWith('password')
                ->same('password'),

            Forms\Components\Select::make('departments')
                ->label('Departments')
                ->relationship('departments', 'name')
                ->multiple()
                ->searchable()
                ->preload()
                ->native(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                // Tampilkan banyak departemen sebagai tags
                Tables\Columns\TagsColumn::make('departments.name')
                    ->label('Departments')
                    ->limit(3),
            ])
            ->filters([
                // Filter by department
                Tables\Filters\SelectFilter::make('departments')
                    ->label('Department')
                    ->relationship('departments', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->hiddenLabel(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['departments']); // eager load relasi many-to-many
    }

    public static function getRelations(): array
    {
        return [
            // Jika mau, kamu bisa tambahkan Relation Manager untuk Departments di sini.
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\UserResource\Pages\ListUsers::route('/'),
            'create' => \App\Filament\Resources\UserResource\Pages\CreateUser::route('/create'),
            'edit' => \App\Filament\Resources\UserResource\Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
