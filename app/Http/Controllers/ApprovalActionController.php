<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Department;
use App\Models\RecruitmentRequest;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApprovalActionController extends Controller
{
    /** Index fase Dirut pada form_data.phases (0-based) */
    private const DIRUT_IDX = 2;

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

        $request = RecruitmentRequest::with('requester', 'department', 'recruitmentPhase')->findOrFail($recruitmentId);
        $requester = $request->requester;
        $assigned = is_null($request->pic_id);

        $userRouteKey = (new User)->getRouteKeyName();
        $user = User::query()->where($userRouteKey, $userId)->firstOrFail();

        $isHrManager = $user->isManager() && $user->isHrDept();

        $isChairman = $user->hasRole('Director')
            && $user->departments()->where('departments.id', $request->department_id)->exists();

        $phase = $request->recruitmentPhase;
        $formData = $phase?->form_data ?? [];

        // Simpan keputusan HR/Direksi
        DB::transaction(function () use ($isHrManager, $isChairman, $isApproved, $approval) {
            if ($isHrManager && is_null($approval->hrd_approval)) {
                $approval->forceFill([
                    'hrd_approval' => $isApproved,
                    'hrd_decided_at' => now(),
                ])->save();
            }

            $dirDecision = $approval->director_approval ?? $approval->chairman_approval;

            if ($isChairman && is_null($dirDecision)) {
                $approval->forceFill([
                    'director_approval' => $isApproved,
                    'director_decided_at' => now(),
                ])->save();
            }
        });

        // === NEW: jika siapa pun (HR/Direksi) REJECT, langsung final reject ===
        if ($isApproved === false) {
            $this->rejectImmediately($approval, $phase, $formData, $request);
        } else {
            // otherwise, jalankan finalize normal
            $approval->refresh();
            $this->finalize($approval, $phase, $formData, $request);
        }

        // ===== Notify (tetap seperti semula) =====
        $recipients = collect();
        if ($requester) $recipients->push($requester);

        $hrDeptId = Department::where('name', 'HRD')->value('id');
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
                'title' => $request->title,
                'actor' => $isChairman ? 'Direksi' : 'HR Manager',
            ],
            actorId: $userId,
            actorName: $user->name,
            department: $request->department?->name,
        );

        if ($isHrManager && $isApproved && $assigned) {
            return redirect()->to("/approvals/{$recruitmentId}/pic/approve");
        }

        $message = $isApproved ? 'Approval disetujui.' : 'Approval tidak disetujui.';
        return redirect()->to(config('app.url'))->with('status', $message);
    }

    /** Reject langsung saat ada pihak menolak (HR/Direksi) */
    private function rejectImmediately(Approval $approval, ?Model $phase, array &$formData, RecruitmentRequest $recruitmentRequest): void
    {
        if (in_array($approval->status, ['approved', 'rejected'], true)) {
            return;
        }

        $nowIso = now()->toIso8601String();

        DB::transaction(function () use ($nowIso, &$formData, $approval, $phase, $recruitmentRequest) {
            // 1) Approval -> rejected
            $approval->forceFill([
                'status'      => 'rejected',
                'approved_at' => now(),
            ])->save();

            // 2) JSON phase: cancel fase "Approval by Stakeholder" (index 1)
            $formData['phases'][1] ??= [];
            $formData['phases'][1]['status']    = 'cancel';
            $formData['phases'][1]['updatedAt'] = $nowIso;

            // 3) Request -> rejected
            $recruitmentRequest->update(['status' => 'rejected']);

            // 4) Phase row -> status = rejected  (INI TAMBAHAN YANG KAMU MAU)
            if ($phase) {
                // jika sudah di-load sebagai instance
                $phase->update([
                    'status'    => 'rejected',
                    'form_data' => $formData,
                ]);
            } else {
                // fallback aman lewat relasi query kalau instance $phase tidak ada
                $recruitmentRequest->recruitmentPhase()->update([
                    'status'    => 'rejected',
                    'form_data' => $formData,
                ]);
            }
        });
    }

    /**
     * Finalisasi status approval & transisi phase.
     * - PERGANTIAN: mengikuti flow lama (HR + Direksi).
     * - PENAMBAHAN: butuh 3 keputusan: HR + Direksi + Dirut (dibaca dari form_data.phases[2].isApproved).
     */
    private function finalize(Approval $approval, Model $phase, array &$formData, RecruitmentRequest $recruitmentRequest): void
    {
        $norm = static function ($v): ?bool {
            if (is_null($v)) return null;
            if (is_bool($v)) return $v;
            if (is_int($v)) return $v === 1;
            if (is_string($v)) {
                $f = filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                return $f;
            }
            return (bool)$v;
        };

        $hrVal = $norm($approval->hrd_approval);
        $dirVals = [
            'director' => $norm($approval->director_approval),
            'chairman' => $norm($approval->chairman_approval),
        ];

        $hrDone = !is_null($hrVal);
        $dirResolved = array_filter($dirVals, static fn($v) => !is_null($v));

        $isPenambahan = Str::lower((string)$recruitmentRequest->recruitment_type) === 'penambahan';

        // ========== PENAMBAHAN: wajib HR + Direksi + Dirut ==========
        if ($isPenambahan) {
            $dirutVal = null;
            if (isset($formData['phases'][self::DIRUT_IDX]['isApproved'])) {
                $dirutVal = $norm($formData['phases'][self::DIRUT_IDX]['isApproved']);
            }
            $dirutDone = !is_null($dirutVal);

            // salah satu belum memutuskan -> jangan finalize
            if (!$hrDone || count($dirResolved) === 0 || !$dirutDone) {
                return;
            }

            if (in_array($approval->status, ['approved', 'rejected'], true)) {
                return;
            }

            // keputusan Direksi
            $dirDecision = null;
            if (count($dirResolved) === 2) {
                $dirDecision = ($dirVals['director'] === true) && ($dirVals['chairman'] === true);
            } else {
                $dirDecision = reset($dirResolved) === true;
            }

            $nowIso = now()->toIso8601String();

            DB::transaction(function () use ($hrVal, $dirDecision, $dirutVal, $nowIso, &$formData, $approval, $phase, $recruitmentRequest) {
                if ($hrVal === true && $dirDecision === true && $dirutVal === true) {
                    // === APPROVED ===
                    $approval->forceFill([
                        'status' => 'approved',
                        'approved_at' => now(),
                    ])->save();

                    // Phase setelah Dirut (index 3) menjadi progress
                    foreach ([1 => 'finish', self::DIRUT_IDX + 1 => 'progress'] as $idx => $status) {
                        if (!isset($formData['phases'][$idx]) || !is_array($formData['phases'][$idx])) {
                            $formData['phases'][$idx] = [];
                        }
                        $formData['phases'][$idx]['status'] = $status;
                        $formData['phases'][$idx]['updatedAt'] = $nowIso;
                    }

                    $recruitmentRequest->status = 'progress';
                    $recruitmentRequest->save();
                } else {
                    // === REJECTED === (salah satu menolak)
                    $approval->forceFill([
                        'status' => 'rejected',
                        'approved_at' => now(),
                    ])->save();

                    if (!isset($formData['phases'][1]) || !is_array($formData['phases'][1])) {
                        $formData['phases'][1] = [];
                    }
                    $formData['phases'][1]['status'] = 'cancel';
                    $formData['phases'][1]['updatedAt'] = $nowIso;

                    $recruitmentRequest->status = 'rejected';
                    $recruitmentRequest->save();
                }

                if ($phase) {
                    $phase->update(['form_data' => $formData]);
                }
            });

            return;
        }

        // ========== PERGANTIAN: flow lama (HR + Direksi) ==========
        if (!$hrDone || count($dirResolved) === 0) {
            return;
        }

        if (in_array($approval->status, ['approved', 'rejected'], true)) {
            return;
        }

        // keputusan Direksi
        $dirDecision = null;
        if (count($dirResolved) === 2) {
            $dirDecision = ($dirResolved['director'] === true) && ($dirResolved['chairman'] === true);
        } else {
            $dirDecision = reset($dirResolved) === true;
        }

        $nowIso = now()->toIso8601String();

        DB::transaction(function () use ($hrVal, $dirDecision, $nowIso, &$formData, $approval, $phase, $recruitmentRequest) {
            if ($hrVal === true && $dirDecision === true) {
                $approval->forceFill([
                    'status' => 'approved',
                    'approved_at' => now(),
                ])->save();

                foreach ([1 => 'finish', 2 => 'progress'] as $idx => $status) {
                    if (!isset($formData['phases'][$idx]) || !is_array($formData['phases'][$idx])) {
                        $formData['phases'][$idx] = [];
                    }
                    $formData['phases'][$idx]['status'] = $status;
                    $formData['phases'][$idx]['updatedAt'] = $nowIso;
                }

                $recruitmentRequest->status = 'progress';
                $recruitmentRequest->save();
            } else {
                $approval->forceFill([
                    'status' => 'rejected',
                    'approved_at' => now(),
                ])->save();

                if (!isset($formData['phases'][1]) || !is_array($formData['phases'][1])) {
                    $formData['phases'][1] = [];
                }
                $formData['phases'][1]['status'] = 'cancel';
                $formData['phases'][1]['updatedAt'] = $nowIso;

                $recruitmentRequest->status = 'rejected';
                $recruitmentRequest->save();
            }

            if ($phase) {
                $phase->update(['form_data' => $formData]);
            }
        });
    }
}
