<?php

namespace App\Http\Controllers;

use App\Livewire\AssignComponent;
use App\Models\Approval;
use App\Models\Department;
use App\Models\RecruitmentRequest;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Livewire\Livewire;

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

        $request = RecruitmentRequest::with('requester', 'department')->findOrFail($recruitmentId);
        $requester = $request->requester;
        $assigned = is_null($request->pic_id);

        // 2) Ambil User berdasar ROUTE KEY (bukan selalu id)
        $userRouteKey = (new User)->getRouteKeyName(); // biasanya 'id' atau 'uuid'
        $user = User::query()->where($userRouteKey, $userId)->firstOrFail();

        $isHrManager = $user->hasRole('Manager') && $user->department && $user->department->name === "HUMAN RESOURCE";
        $isChairman  = $user->hasRole('Director');

        if ($isHrManager) {
            if (!is_null($approval->hrd_approval)) {
                if($assigned){
                    return redirect()->to("/approvals/{$recruitmentId}/pic/approve");
                }
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

        $hrDone  = !is_null($approval->hrd_approval);
        $dirDone = !is_null($approval->director_approval ?? null);

        if ($hrDone && $dirDone) {
            $approval->forceFill([
                'status'      => ($approval->hrd_approval && $approval->director_approval) ? 'approved' : 'rejected',
                'approved_at' => now(),
            ])->save();
        }

        $recipients = collect();

        if ($requester) {
            $recipients->push($requester);
        }

        $hrDeptId = Department::where('name', 'HUMAN RESOURCE')->value('id');
        $hrManagers = User::whereHas('roles', fn($q) => $q->where('name', 'Manager'))
            ->where('department_id', $hrDeptId)
            ->get();

        $recipients = $recipients->merge($hrManagers)->unique('id');

        Notify::recruitmentActivity(
            recipients: $recipients,
            recruitmentId: $recruitmentId,
            action: $isChairman ? 'direksi_approval' : 'hrmanager_approval',
            context: [
                'status' => $isApproved,
                'title' => $request->title,
                'actor' => $isChairman ? 'Direksi' : 'HR Manager',
            ],
            actorId: $userId,
            actorName: $user->name,
            department: $request->department?->name,
        );

        $message = $isApproved ? 'Approval disetujui.' : 'Approval tidak disetujui.';

        if($isHrManager && $isApproved){
            return redirect()->to("/approvals/{$recruitmentId}/pic/approve");
        }
        return redirect()->to(config('app.url'))->with('status', $message);
    }

    private function statusText(?bool $v): string
    {
        return $v ? 'disetujui' : 'ditolak';
    }
}
