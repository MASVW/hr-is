<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('signed')->group(function () {
    Route::get('/approvals/{recruitmentId}/{userId}/approve', [\App\Http\Controllers\ApprovalActionController::class, 'approve'])
        ->name('approvals.approve');
    Route::get('/approvals/{recruitmentId}/{userId}/reject',  [\App\Http\Controllers\ApprovalActionController::class, 'reject'])
        ->name('approvals.reject');
});
