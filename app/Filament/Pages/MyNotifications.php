<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class MyNotifications extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static string $view = 'filament.pages.my-notifications';
    protected static ?string $navigationLabel = 'Notifications';
    protected static ?string $navigationGroup = 'Utilities';
    protected static ?int $navigationSort = 99;

    public function getHeading(): string { return 'Notifications'; }
}
