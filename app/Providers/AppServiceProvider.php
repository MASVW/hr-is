<?php

namespace App\Providers;

use App\Models\RecruitmentPhase;
use App\Models\RecruitmentRequest;
use App\Observers\RecruitmentPhaseObserver;
use App\Observers\RecruitmentRequestObserver;
use Filament\Notifications\Livewire\DatabaseNotifications;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DatabaseNotifications::pollingInterval('5s');
        RecruitmentRequest::observe(RecruitmentRequestObserver::class);
        RecruitmentPhase::observe(RecruitmentPhaseObserver::class);
    }
}
