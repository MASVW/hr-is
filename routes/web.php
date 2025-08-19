<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('approvals')->group(function () {
    Route::get('{v}/approve/{userId}', [\App\Http\Controllers\ApprovalActionController::class, 'approve'])
        ->name('approvals.approve')->middleware('signed');
    Route::get('{recruitmentId}/reject/{userId}', [\App\Http\Controllers\ApprovalActionController::class, 'reject'])
        ->name('approvals.reject')->middleware('signed');
});
