<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin', 301);
});

Route::get('/approvals/{recruitmentId}/pic/approve', \App\Livewire\AssignComponent::class)
    ->name('approvals.pic.approve')
    ->middleware('signed:relative');

Route::middleware('signed')->group(function () {
    Route::get('/approvals/{recruitmentId}/{userId}/approve', [\App\Http\Controllers\ApprovalActionController::class, 'approve'])
        ->name('approvals.approve');
    Route::get('/approvals/{recruitmentId}/{userId}/reject',  [\App\Http\Controllers\ApprovalActionController::class, 'reject'])
        ->name('approvals.reject');
});



