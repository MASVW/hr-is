<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\RecruitmentRequest;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector as LivewireRedirector;

class AssignComponent extends Component
{
    public string $recruitmentId;
    public RecruitmentRequest $recruitment;
    public $actor;

    public ?string $pic_id = null;

    /** @var array<int, array{id:string,name:string,email:string|null}> */
    public array $hrStaff = [];

    // simpan id departemen HR (tidak dipakai di rules lagi)
    private ?string $hrDeptId = null;

    public function mount(string $recruitmentId): void
    {
        $this->recruitmentId = $recruitmentId;
        $this->recruitment   = RecruitmentRequest::with('department')->findOrFail($recruitmentId);

        $this->hrDeptId = Department::whereRaw('UPPER(TRIM(name)) = ?', ['HRD'])->value('id');

        // Ambil user role=Staff yang berada di departemen HR (many-to-many)
        $this->hrStaff = User::query()
            ->whereHas('roles', fn ($q) => $q->whereRaw('UPPER(TRIM(name)) = ?', ['STAFF']))
            ->when($this->hrDeptId, fn ($q) =>
            $q->whereHas('departments', fn ($d) => $d->where('departments.id', $this->hrDeptId))
            )
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get()
            ->toArray();

        // preselect jika sudah ada PIC
        $this->pic_id = $this->recruitment->pic_id;
    }

    protected function rules(): array
    {
        // batasi pilihan hanya ke HR Staff yang sudah di-load
        $allowedIds = collect($this->hrStaff)->pluck('id')->all();

        return [
            'pic_id' => [
                'required',
                'uuid',
                Rule::in($allowedIds),
            ],
        ];
    }


    public function save()
    {
        $this->validate();

        $this->recruitment->forceFill([
            'pic_id' => $this->pic_id,
        ])->save();

        session()->flash('success', 'PIC berhasil ditetapkan.');

        Notify::assignPICActivity(
            recipients: [$this->pic_id],
            recruitmentId: (string) $this->recruitment->getKey(),
            action: 'assignTo',
            context: [
                'title' => $this->recruitment->title,
            ],
            actorId: (string) ($actor->id ?? 'system'),
            actorName: $auth()->user()->name ?? 'Manager HRD',
            department: $this->recruitment->department->name ?? null,
        );

        return redirect()->to(config('app.url'));
    }

    public function render(): View
    {
        return view('livewire.assign-component')
            ->layout('layout.guest', ['title' => 'Assign PIC']);
    }
}
