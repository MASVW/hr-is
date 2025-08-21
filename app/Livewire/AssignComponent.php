<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AssignComponent extends Component
{
    public string $recruitmentId;
    public RecruitmentRequest $recruitment;

    // ganti ke 'pic' jika kolommu masih 'pic'
    public ?string $pic_id = null;

    /** @var array<int, array{id:string,name:string,email:string|null}> */
    public array $hrStaff = [];

    public function mount(string $recruitmentId): void
    {
        $this->recruitmentId = $recruitmentId;
        $this->recruitment   = RecruitmentRequest::with('department')->findOrFail($recruitmentId);

        $hrDeptId = Department::where('name', 'HUMAN RESOURCE')->value('id');

        $this->hrStaff = User::role('Staff')
            ->where('department_id', $hrDeptId)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get()
            ->toArray();

        $this->pic_id = $this->recruitment->pic_id;
    }

    protected function rules(): array
    {
        return [
            'pic_id' => ['required', 'uuid', Rule::exists('users', 'id')],
        ];
    }

    public function save()
    {
        $this->validate();

        $this->recruitment->forceFill([
            'pic_id' => $this->pic_id,
        ])->save();

        session()->flash('success', 'PIC berhasil ditetapkan.');

        return redirect()->to(config('app.url'));
    }

    public function render()
    {
        return view('livewire.assign-component')
            ->layout('layout.guest', ['title' => 'Assign PIC']);
    }
}
