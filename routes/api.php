<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EmailApiController;

Route::get('/ping', fn () => response()->json(['message' => 'pong']));

Route::middleware('hapi.js')->prefix('email')->group(function () {
    Route::post('/notify',   [EmailApiController::class, 'notify'])->name('api.email.notify');
    Route::post('/approval', [EmailApiController::class, 'approval'])->name('api.email.approval');
});
