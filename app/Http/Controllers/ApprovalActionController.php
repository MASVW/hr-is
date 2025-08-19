<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
        $approval = Approval::where('request_id', $recruitmentId)->firstOrFail();
        $user = User::findOrFail($userId);

        $isHrManager = $user->hasRole('HR Manager');
        $isChairman  = $user->hasRole('Director');

        if ($isHrManager) {
            if (!is_null($approval->hrd_approval)) {
                return redirect()->to(config('app.url'))
                    ->with('status', "Approval sudah {$approval->status}.");
            }
            $approval->forceFill([
                'hrd_approval'               => $isApproved,
                'hrd_decided_at'       => now(),
            ])->save();
        }

        if ($isChairman) {
            if (!is_null($approval->chairman_approval)) {
                return redirect()->to(config('app.url'))
                    ->with('status', "Approval sudah {$approval->status}.");
            }
            $approval->forceFill([
                'director_approval'               => $isApproved,
                'director_decided_at'  => now(),
            ])->save();
        }

        if (!is_null($approval->hrd_approval) && !is_null($approval->chairman_approval)) {
            $approval->forceFill([
                'status'     => $approval->hrd_approval && $approval->chairman_approval ? 'approved' : 'rejected',
                'approved_at'=> now(),
            ])->save();
        }

        $message = $isApproved ? 'Approval disetujui.' : 'Approval tidak disetujui.';
        return redirect()->to(config('app.url'))->with('status', $message);
    }
}
