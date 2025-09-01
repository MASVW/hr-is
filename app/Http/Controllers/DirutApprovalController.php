<?php

namespace App\Http\Controllers;

use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DirutApprovalController extends Controller
{
    private const DIRUT_IDX = 2; // 0-based

    public function approve(string $recruitmentId, string $userId): RedirectResponse
    {
        $req = RecruitmentRequest::with('recruitmentPhase', 'approval')->findOrFail($recruitmentId);

        $userRouteKey = (new User)->getRouteKeyName();
        $user         = User::where($userRouteKey, $userId)->firstOrFail();
        abort_unless($user->hasRole('Dirut'), 403, 'Hanya Dirut yang berhak melakukan aksi ini.');

        $phase  = $req->recruitmentPhase;
        $form   = $phase?->form_data ?? [];
        $nowIso = now()->toIso8601String();
        $idx    = self::DIRUT_IDX;

        $form['phases'] ??= [];
        $form['phases'][$idx] ??= [];
        $form['phases'][$idx]['isApproved'] = true;
        $form['phases'][$idx]['status']     = 'finish';
        $form['phases'][$idx]['note']       = '';
        $form['phases'][$idx]['updatedAt']  = $nowIso;

        DB::transaction(function () use ($phase, $form) {
            if ($phase) {
                $phase->update(['form_data' => $form]);
            }
        });

        Log::info('dirut.approve', [
            'req' => $recruitmentId,
            'user' => $userId,
            'idx' => self::DIRUT_IDX,
        ]);

        return redirect()->to(config('app.url'))->with('status', 'Persetujuan Dirut disetujui.');
    }
}
