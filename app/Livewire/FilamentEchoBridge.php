<?php

// app/Livewire/FilamentEchoBridge.php
namespace App\Livewire;

use Livewire\Component;
use Filament\Notifications\Notification;

class FilamentEchoBridge extends Component
{
    public function pushToast(string $status = 'info', string $title = '', string $body = '')
    {
        $n = Notification::make()->title($title)->body($body);

        match ($status) {
            'success' => $n->success(),
            'warning' => $n->warning(),
            'danger', 'error' => $n->danger(),
            default => $n->info(),
        };

        $n->send(); // tampil sebagai toast Filament
    }

    public function render() { return view('livewire.filament-echo-bridge'); }
}
