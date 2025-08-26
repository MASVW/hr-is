<?php

namespace App\Filament\Resources;

use App\Models\User;
use App\Support\AccessHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $activeNavigationIcon = 'heroicon-s-user';
    protected static ?int $navigationSort = 5;

    // === Gate via helper (tetap) ===
    public static function canAccess(): bool { return AccessHelper::canAccessHR(); }
    public static function canView(Model $record): bool { return AccessHelper::canViewHR(); }
    public static function canEdit(Model $record): bool { return AccessHelper::canEditHR(); }
    public static function canDelete(Model $record): bool { return AccessHelper::canDeleteHR(); }
    public static function canCreate(): bool { return AccessHelper::canCreateHR(); }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identitas')
                ->description('Data dasar akun pengguna.')
                ->icon('heroicon-o-identification')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Nama pengguna'),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        // Jika Filament >= v3: unique(ignoreRecord: true)
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->placeholder('user@perusahaan.com'),

                    // Password: wajib saat create, opsional saat edit; hash hanya jika diisi
                    Forms\Components\TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->maxLength(255)
                        ->required(fn (string $context) => $context === 'create')
                        ->rule('min:8')
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->helperText('Minimal 8 karakter. Kosongkan saat edit jika tidak ingin mengubah.'),

                    Forms\Components\TextInput::make('password_confirmation')
                        ->label('Konfirmasi Password')
                        ->password()
                        ->revealable()
                        ->dehydrated(false)
                        ->requiredWith('password')
                        ->same('password'),
                ]),

            Forms\Components\Section::make('Akses & Organisasi')
                ->description('Atur role dan departemen untuk user.')
                ->icon('heroicon-o-shield-check')
                ->columns(2)
                ->schema([
                    // === MULTI ROLE (Spatie) ===
                    Forms\Components\Select::make('roles')
                        ->label('Roles')
                        ->relationship(
                            name: 'roles',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query->where('guard_name', 'web')->orderBy('name')
                        )
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->native(false)
                        ->helperText('Bisa pilih lebih dari satu role.'),

                    Forms\Components\Select::make('departments')
                        ->label('Departments')
                        ->relationship('departments', 'name', fn ($query) => $query->orderBy('name'))
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->native(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TagsColumn::make('roles.name')
                    ->label('Roles')
                    ->limit(3)
                    ->badge(),

                Tables\Columns\TagsColumn::make('departments.name')
                    ->label('Departments')
                    ->limit(3)
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name'),

                Tables\Filters\SelectFilter::make('departments')
                    ->label('Department')
                    ->relationship('departments', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->hiddenLabel(),
                Tables\Actions\EditAction::make()->hiddenLabel(),
                Tables\Actions\DeleteAction::make()->hiddenLabel(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // Eager load relasi agar tabel & filter hemat query
        return parent::getEloquentQuery()
            ->with(['roles', 'departments']);
    }

    public static function getRelations(): array
    {
        return [
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
