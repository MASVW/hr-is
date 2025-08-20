<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class ApprovalActionController extends Controller
{
    public function approve(string $recruitmentId, string $userId): RedirectResponse
    {
        return $this->handleApproval($recruitmentId, $userId, true);
    }

    public function reject(string $recruitmentId, string $userId): RedirectResponse
    {
        return $this->handleApproval($recruitmentId, $userId, false);
    }

    private function handleApproval(string $recruitmentId, string $userId, bool $isApproved): RedirectResponse
    {
        // 1) Ambil Approval berdasarkan request_id (harus sama dengan recruitmentId di URL)
        $approval = Approval::query()
            ->where('request_id', $recruitmentId)
            ->firstOrFail();

        // 2) Ambil User berdasar ROUTE KEY (bukan selalu id)
        $userRouteKey = (new User)->getRouteKeyName(); // biasanya 'id' atau 'uuid'
        $user = User::query()->where($userRouteKey, $userId)->firstOrFail();

        $isHrManager = $user->hasRole('Manager') && $user->department && $user->department->name === "HUMAN RESOURCE";
        $isChairman  = $user->hasRole('Director');

        if ($isHrManager) {
            if (!is_null($approval->hrd_approval)) {
                return redirect()->to(config('app.url'))
                    ->with('status', "Keputusan HR sudah {$this->statusText($approval->hrd_approval)}.");
            }
            $approval->forceFill([
                'hrd_approval'   => $isApproved,
                'hrd_decided_at' => now(),
            ])->save();
        }

        if ($isChairman) {
            if (!is_null($approval->chairman_approval ?? $approval->director_approval)) {
                return redirect()->to(config('app.url'))
                    ->with('status', "Keputusan Direktur sudah {$this->statusText($approval->chairman_approval ?? $approval->director_approval)}.");
            }
            $approval->forceFill([
                'director_approval'   => $isApproved,
                'director_decided_at' => now(),
            ])->save();
        }

        // 4) Jika dua pihak sudah memutuskan → set status akhir
        $hrDone  = !is_null($approval->hrd_approval);
        $dirDone = !is_null($approval->director_approval ?? null);

        if ($hrDone && $dirDone) {
            $approval->forceFill([
                'status'      => ($approval->hrd_approval && $approval->director_approval) ? 'approved' : 'rejected',
                'approved_at' => now(),
            ])->save();
        }

        $message = $isApproved ? 'Approval disetujui.' : 'Approval tidak disetujui.';
        return redirect()->to(config('app.url'))->with('status', $message);
    }

    private function statusText(?bool $v): string
    {
        return $v ? 'disetujui' : 'ditolak';
    }
}
