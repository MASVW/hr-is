<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});

Route::middleware(['hapi.sign'])->prefix('email')->group(function () {
    Route::post('notify',  [\App\Http\Controllers\Api\EmailApiController::class, 'notify']);
    Route::post('approval', [\App\Http\Controllers\Api\EmailApiController::class, 'approval']);
});
