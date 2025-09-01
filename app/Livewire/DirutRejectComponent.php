<?php

namespace App\Livewire;

use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DirutRejectComponent extends Component
{
    public string $recruitmentId;
    public string $userId;

    public RecruitmentRequest $recruitment;

    public string $note = '';

    public function mount(string $recruitmentId, string $userId): void
    {
        $this->recruitmentId = $recruitmentId;
        $this->userId        = $userId;

        $this->recruitment = RecruitmentRequest::with('recruitmentPhase', 'department', 'approval')
            ->findOrFail($recruitmentId);

        abort_unless(strtolower((string) $this->recruitment->recruitment_type) === 'penambahan', 403);

        // Pastikan user adalah Dirut (cek case-insensitive agar robust)
        $userRouteKey = (new User)->getRouteKeyName();
        $user         = User::where($userRouteKey, $userId)->firstOrFail();
        $isDirut      = $user->roles()->whereRaw('UPPER(name) = ?', ['DIRUT'])->exists();
        abort_unless($isDirut, 403, 'Hanya Dirut yang berhak melakukan aksi ini.');
    }

    protected function rules(): array
    {
        return [
            'note' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function save()
    {
        $this->validate();

        $phase  = $this->recruitment->recruitmentPhase;
        $form   = $phase?->form_data ?? [];
        $nowIso = now()->toIso8601String();

        // Index fase Dirut (0-based)
        $idx = \App\Support\ApprovalFlow::DIRUT_IDX; // pastikan konstanta ini = 2

        // Mutasi JSON phases[2]
        $form['phases'] ??= [];
        if (!isset($form['phases'][$idx]) || !is_array($form['phases'][$idx])) {
            $form['phases'][$idx] = [];
        }
        $form['phases'][$idx]['isApproved'] = false;
        $form['phases'][$idx]['status']     = 'cancel';
        $form['phases'][$idx]['note']       = $this->note;
        $form['phases'][$idx]['updatedAt']  = $nowIso;

        DB::transaction(function () use ($form) {
            $this->recruitment->recruitmentPhase()->update([
                'form_data' => $form,
                'status'    => 'rejected',
            ]);

            $this->recruitment->update(['status' => 'rejected']);

            $this->recruitment->approval()?->update([
                'status'      => 'rejected',
                'approved_at' => now(),
            ]);
        });

        session()->flash('success', 'Penolakan Dirut telah disimpan.');
        return redirect()->to(config('app.url'));
    }

    public function render(): View
    {
        return view('livewire.dirut-reject-component')
            ->layout('layout.guest', ['title' => 'Keputusan Dirut']);
    }
}
