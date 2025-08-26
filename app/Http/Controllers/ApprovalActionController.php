<?php

namespace App\Http\Controllers;

use App\Livewire\AssignComponent;
use App\Models\Approval;
use App\Models\Department;
use App\Models\RecruitmentRequest;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

class ApprovalActionController extends Controller{
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
        $approval = Approval::query()
            ->with('request')
            ->where('request_id', $recruitmentId)
            ->firstOrFail();

        $request   = RecruitmentRequest::with('requester', 'department', 'recruitmentPhase')->findOrFail($recruitmentId);
        $requester = $request->requester;
        $assigned  = is_null($request->pic_id);

        $userRouteKey = (new User)->getRouteKeyName();
        $user = User::query()->where($userRouteKey, $userId)->firstOrFail();

        $isHrManager = $user->isManager() && $user->isHrDept();

        $isChairman = $user->hasRole('Director')
            && $user->departments()->where('departments.id', $request->department_id)->exists();

        $phase    = $request->recruitmentPhase;
        $formData = $phase?->form_data ?? [];

        DB::transaction(function () use ($isHrManager, $isChairman, $isApproved, $approval) {
            if ($isHrManager && is_null($approval->hrd_approval)) {
                $approval->forceFill([
                    'hrd_approval'   => $isApproved,
                    'hrd_decided_at' => now(),
                ])->save();
            }

            $dirDecision = $approval->director_approval ?? $approval->chairman_approval;

            if ($isChairman && is_null($dirDecision)) {
                $approval->forceFill([
                    'director_approval'   => $isApproved,
                    'director_decided_at' => now(),
                ])->save();
            }
        });

        $approval->refresh();
        $this->finalize($approval, $phase, $formData);

        $recipients = collect();
        if ($requester) $recipients->push($requester);

        $hrDeptId = Department::where('name', 'HUMAN RESOURCE')->value('id');
        $hrManagers = User::whereHas('roles', fn($q) => $q->where('name', 'Manager'))
            ->whereHas('departments', fn($q) => $q->where('id', $hrDeptId))
            ->get();

        $recipients = $recipients->merge($hrManagers)->unique('id');

        Notify::recruitmentActivity(
            recipients: $recipients,
            recruitmentId: $recruitmentId,
            action: $isChairman ? 'direksi_approval' : 'hrmanager_approval',
            context: [
                'status' => $isApproved,
                'title'  => $request->title,
                'actor'  => $isChairman ? 'Direksi' : 'HR Manager',
            ],
            actorId: $userId,
            actorName: $user->name,
            department: $request->department?->name,
        );

        // Jika HR approve & belum ada PIC → arahkan ke assign PIC
        if ($isHrManager && $isApproved && $assigned) {
            return redirect()->to("/approvals/{$recruitmentId}/pic/approve");
        }

        $message = $isApproved ? 'Approval disetujui.' : 'Approval tidak disetujui.';
        return redirect()->to(config('app.url'))->with('status', $message);
    }

    private function finalize(Approval $approval, ?\Illuminate\Database\Eloquent\Model $phase, array &$formData): void
    {
        $hrDone      = !is_null($approval->hrd_approval);
        $dirDecision = $approval->director_approval ?? $approval->chairman_approval; // dukung data lama
        $dirDone     = !is_null($dirDecision);

        if (!($hrDone && $dirDone)) {
            return; // belum lengkap, tunda finalisasi
        }

        if (in_array($approval->status, ['approved', 'rejected'], true)) {
            return;
        }

        if ($approval->hrd_approval && $dirDecision) {
            $approval->forceFill([
                'status'      => 'approved',
                'approved_at' => now(),
            ])->save();

            $formData['phases'][1]['status']    = 'finish';
            $formData['phases'][2]['status']    = 'progress';
            $formData['phases'][1]['updatedAt'] = now()->toISOString();
        } else {
            $approval->forceFill([
                'status'      => 'rejected',
                'approved_at' => now(),
            ])->save();

            $formData['phases'][1]['status']    = 'cancel';
            $formData['phases'][1]['updatedAt'] = now()->toISOString();
        }

        if ($phase) {
            $phase->update(['form_data' => $formData]);
        }
    }
}
