<?php

namespace App\Support;

use App\Models\Approval;
use App\Models\RecruitmentRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ApprovalFlow
{
    public const MANAGER_IDX = 1;
    public const DIRUT_IDX   = 2;

    public static function attemptFinalizeTripleApproval(
        Approval $approval,
        RecruitmentRequest $request,
        ?Model $phase,
        int $managerIdx = self::MANAGER_IDX,
        int $dirutIdx   = self::DIRUT_IDX,
    ): void {
        $form   = $phase?->form_data ?? [];
        $phases = Arr::get($form, 'phases', []);

        $hrOk      = filter_var($approval->hrd_approval, FILTER_VALIDATE_BOOLEAN);
        $managerOk = (bool) Arr::get($phases, "{$managerIdx}.isApproved", false);
        $dirutOk   = (bool) Arr::get($phases, "{$dirutIdx}.isApproved", false);

        if (!($hrOk && $managerOk && $dirutOk)) {
            return;
        }
        if ($approval->status === 'approved') {
            return;
        }

        $nowIso  = now()->toIso8601String();
        $nextIdx = $dirutIdx + 1;

        DB::transaction(function () use ($approval, $request, $phase, &$form, $nowIso, $nextIdx) {
            $approval->forceFill([
                'status'      => 'approved',
                'approved_at' => now(),
            ])->save();

            $form['phases'] ??= [];
            $form['phases'][$nextIdx] ??= [];
            $form['phases'][$nextIdx]['status']    = 'progress';
            $form['phases'][$nextIdx]['updatedAt'] = $nowIso;

            $request->status = 'progress';
            $request->save();

            if ($phase) {
                $phase->update(['form_data' => $form]);
            }
        });
    }
}
