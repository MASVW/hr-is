<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin', 301);
});

Route::get('/approvals/{recruitmentId}/pic/approve', \App\Livewire\AssignComponent::class)
    ->name('approvals.pic.approve');

Route::middleware('signed:relative')->group(function () {
    Route::get('/approvals/{recruitmentId}/{userId}/approve', [\App\Http\Controllers\ApprovalActionController::class, 'approve'])
        ->name('approvals.approve');
    Route::get('/approvals/{recruitmentId}/{userId}/reject',  [\App\Http\Controllers\ApprovalActionController::class, 'reject'])
        ->name('approvals.reject');
});


Route::get('/__debug/signed', function () {
    $u = \Illuminate\Support\Facades\URL::temporarySignedRoute(
        'approvals.approve',
        now()->addMinutes(10),
        ['recruitmentId' => 'test', 'userId' => '1']
    );
    \Illuminate\Support\Facades\Log::info('GEN_URL='.$u.' APP_URL='.config('app.url'));
    return $u;
});

Route::get('/approvals/{recruitmentId}/{userId}/approve/debug', function (\Illuminate\Http\Request $r) {
    // sementara TANPA middleware untuk lihat detail apa yang dihitung
    $prepared = $r->url();
    $qs = \Illuminate\Support\Arr::query(collect($r->query())->except('signature')->sortKeys()->all());
    if ($qs !== '') $prepared .= '?'.$qs;

    $key = config('app.key');
    if (\Illuminate\Support\Str::startsWith($key, 'base64:')) $key = base64_decode(substr($key, 7));

    $expected = hash_hmac('sha256', $prepared, $key);

    return response()->json([
        'request_url' => $r->fullUrl(),
        'prepared'    => $prepared,
        'got_sig'     => $r->query('signature'),
        'exp_sig'     => $expected,
        'valid'       => hash_equals($r->query('signature', ''), $expected),
        'app_url'     => config('app.url'),
    ]);
});

Route::get('/__debug/relative', function () {
    $p = \Illuminate\Support\Facades\URL::temporarySignedRoute('approvals.approveDebug', now()->addMinutes(10), [
        'recruitmentId' => 'test', 'userId' => '1'
    ], absolute: false);

    return response()->json([
        'relative' => $p,
        'absolute' => \Illuminate\Support\Facades\URL::to($p)
    ]);
});
