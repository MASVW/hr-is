<?php

namespace App\Filament\Resources\RecruitmentPhaseResource\Pages;

use App\Filament\Resources\RecruitmentPhaseResource;
use App\Models\Department;
use App\Models\User;
use App\Support\Emailer;
use App\Support\Notify;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification as FNotification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EditRecruitmentPhase extends EditRecord
{
    protected array $labelAcronyms = [
        'CV', 'HR', 'HRD', 'SPV', 'SU', 'ID', 'NIK', 'URL', 'API', 'SAP', 'IT', 'GA', 'QA', 'QC', 'PPIC'
    ];
    protected static string $resource = RecruitmentPhaseResource::class;

    public ?int $pendingIndex = null;
    public ?string $pendingNewStatus = null;
    public ?int $editedIndex = null;

    public ?string $department = null;
    public ?string $departmentId = null;
    public ?string $hrDepartmentId = null;

    public array $accumulatedChanges = [];
    public array $latestChanges = [];

    /** key yang tidak disertakan ketika diff */
    protected array $diffExcludeKeys = ['form_data', 'status'];

    protected function beforeFill(): void
    {
        $r = $this->getRecord();

        $this->department = $r?->recruitmentRequest?->department?->name ?? null;
        $this->departmentId = $r?->recruitmentRequest?->department?->id ?? null;
        $this->hrDepartmentId = Department::where('name', 'HUMAN RESOURCE')->value('id');
    }

    public function form(Form $form): Form
    {
        $statusOption = [
            'finish' => 'Finished',
            'progress' => 'On Progress',
            'pending' => 'Pending',
        ];

        return $form
            ->schema([
                TextInput::make('status')
                    ->disabled()
                    ->dehydrated(false)
                    ->afterStateHydrated(fn($component) => $component->state(fn($state) => ucfirst((string)$state)))
                    ->required()
                    ->maxLength(255),

                DateTimePicker::make('started_at')
                    ->disabled()
                    ->dehydrated(false),

                Section::make('Form Data')
                    ->description(function () {
                        $phases = $this->record->form_data['phases'] ?? [];
                        foreach ($phases as $phase) {
                            if (($phase['status'] ?? null) === 'progress') {
                                return 'Dalam Tahap ' . ($phase['name'] ?? '-');
                            }
                        }
                        return '-';
                    })
                    ->statePath('form_data')
                    ->schema([
                        Tabs::make('Phases')
                            ->tabs(fn(Get $get) => $this->buildDynamicPhaseTabs($get, $statusOption))
                            ->activeTab(fn() => $this->getActivePhaseIndex()) // fokus ke progress; fallback pending; lalu 0
                            ->extraAttributes([
                                // Auto-scroll ke tab aktif saat awal render & tiap event dipicu
                                'x-data' => '{}',
                                'x-init' => 'setTimeout(() => {
                                    const active = $el.querySelector("[role=tab][aria-selected=true]");
                                    if (active) {
                                        const list = active.closest("[role=tablist]") ?? active.parentElement;
                                        if (list) {
                                            const left = active.offsetLeft - (list.clientWidth - active.clientWidth)/2;
                                            list.scrollTo({ left: Math.max(0, left), behavior: "smooth" });
                                        }
                                    }
                                }, 60)',
                                'x-on:scroll-active-tab.window' => '
                                    const active = $el.querySelector("[role=tab][aria-selected=true]");
                                    if (active) {
                                        const list = active.closest("[role=tablist]") ?? active.parentElement;
                                        if (list) {
                                            const left = active.offsetLeft - (list.clientWidth - active.clientWidth)/2;
                                            list.scrollTo({ left: Math.max(0, left), behavior: "smooth" });
                                        }
                                    }
                                ',
                            ])
                            ->reactive(),
                    ]),
            ]);
    }

    /** Bangun tabs fase secara dinamis */
    protected function buildDynamicPhaseTabs(Get $get, array $statusOption): array
    {
        $tabs = [];
        $phases = $this->getRecord()?->form_data['phases'] ?? [];
        foreach ($phases as $i => $phase) {
            $idx = $i;

            $tabs[] = Tabs\Tab::make($phase['name'] ?? ('Phase #' . ($idx + 1)))
                ->statePath("phases.$idx")
                ->icon('heroicon-o-document-text')
                ->badge(function ($record) use ($idx) {
                    $st = data_get($record->form_data, "phases.$idx.status");
                    return $st ? ucfirst($st) : '';
                })
                ->badgeColor(function ($record) use ($idx) {
                    return match (data_get($record->form_data, "phases.$idx.status")) {
                        'progress' => 'success',
                        'finish' => 'primary',
                        default => 'gray',
                    };
                })
                ->schema($this->makePhaseFields($idx, (array)$phase, $statusOption))
                ->dehydrated(true);
        }

        return $tabs;
    }

    /** Field per-phase */
    protected function makePhaseFields(int $index, array $phase, array $statusOption): array
    {
        $fields = [];

        // STATUS (diproses manual via onPhaseStatusChange)
        $fields[] = Select::make('status')
            ->dehydrated(false)
            ->live()
            ->reactive()
            ->options($statusOption)
            ->selectablePlaceholder(false)
            ->afterStateUpdated(function (string $state, Set $set, Get $get) use ($index) {
                $this->onPhaseStatusChange($index, $state, $get, $set);
            });

        // Note
        $fields[] = Textarea::make('note')->label('Note')->columnSpanFull();

        // Revise Notes
        $fields[] = Textarea::make('reviseNotes')
            ->dehydrated(true)
            ->afterStateHydrated(fn($component) => $component->state(''))
            ->label('Revise Notes')
            ->helperText('Wajib diisi saat mengubah dari Finished ke Pending / On Progress.')
            ->debounce(300)
            ->live()
            ->reactive()
            ->required(fn() => $this->editedIndex === $index && $this->pendingNewStatus !== null)
            ->hidden(fn() => $this->editedIndex !== $index)
            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                if (filled($state)) {
                    $this->tryApplyPendingChange($get, $set);
                }
            });

        // Abaikan beberapa key (termasuk alias typo 'finisihed')
        $ignored = ['name', 'status', 'note', 'updatedAt', 'reviseNotes', 'form_data', 'finisihed'];

        // Alias finished/finisihed => render SATU field 'finished'
        $finishedKey = array_key_exists('finished', $phase)
            ? 'finished'
            : (array_key_exists('finisihed', $phase) ? 'finisihed' : null);

        if ($finishedKey !== null) {
            $value = $phase[$finishedKey] ?? 0;
            $fields[] = TextInput::make('finished')
                ->label('Finished')
                ->numeric()
                ->afterStateHydrated(function ($component) use ($value) {
                    $component->state($value ?? 0);
                })
                ->dehydrated(true);
        }

        // Field dinamis lain
        foreach ($phase as $key => $value) {
            if (in_array($key, $ignored, true)) continue;

            if ($key === 'isApproved') {
                $fields[] = Toggle::make($key)->label('Approved')->dehydrated(true);
                continue;
            }

            if ($key === 'candidate' && is_array($value)) {
                $fields[] = Repeater::make('candidate')
                    ->label('Candidate')
                    ->schema([
                        TextInput::make('name')->label('Name')->maxLength(150),
                        TextInput::make('position')->label('Position')->maxLength(150),
                        DatePicker::make('onBoardingDate')->label('On Boarding Date'),
                    ])
                    ->collapsible()
                    ->grid(3)
                    ->dehydrated(true)
                    ->columns(3);
                continue;
            }

            if (is_int($value) || is_float($value) || $this->isNumericLike($value, $key)) {
                $fields[] = TextInput::make($key)
                    ->label($this->labelize($key))
                    ->numeric()
                    ->dehydrated(true);
                continue;
            }

            if (is_bool($value)) {
                $fields[] = Toggle::make($key)
                    ->label($this->labelize($key))
                    ->dehydrated(true);
                continue;
            }

            if (is_string($value)) {
                $length = mb_strlen($value);
                if ($length > 120) {
                    $fields[] = Textarea::make($key)
                        ->label($this->labelize($key))
                        ->rows(3)
                        ->dehydrated(true);
                } else {
                    $fields[] = TextInput::make($key)
                        ->label($this->labelize($key))
                        ->dehydrated(true);
                }
                continue;
            }

            $fields[] = Textarea::make($key)
                ->label($this->labelize($key))
                ->rows(3)
                ->helperText('Format bebas / JSON.')
                ->dehydrated(true);
        }

        return $fields;
    }

    protected function labelize(string $key): string
    {
        // 1) Normalisasi pemisah & sisipkan spasi hanya pada lower->Upper
        $s = preg_replace('/[_-]+/', ' ', $key);
        $s = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $s);
        $s = trim(preg_replace('/\s+/', ' ', $s));

        // 2) Titlecase biasa, tapi pertahankan akronim tetap UPPER
        $parts = explode(' ', $s);
        $acros = $this->labelAcronyms ?? [];
        $parts = array_map(function ($w) use ($acros) {
            $upper = strtoupper($w);
            if (in_array($upper, $acros, true)) {
                return $upper;
            }
            return ucfirst(strtolower($w));
        }, $parts);

        return implode(' ', $parts);
    }

    protected function isNumericLike($v, string $key): bool
    {
        $knownNumeric = [
            'totalCV', 'approvedCV', 'checked', 'interviewed',
            'candidate', 'passed', 'agreed', 'offered', 'onboarded', 'hasChecked',
        ];
        if (in_array($key, $knownNumeric, true)) return true;
        return is_string($v) && is_numeric($v);
    }

    protected function requiresReviseNotes(?string $oldStatus, ?string $newStatus): bool
    {
        return $oldStatus === 'finish' && in_array($newStatus, ['progress', 'pending'], true);
    }

    protected function ensureReviseNotesArray(array &$phase): void
    {
        if (!array_key_exists('reviseNotes', $phase)) {
            $phase['reviseNotes'] = [];
            return;
        }

        if (is_string($phase['reviseNotes'])) {
            $phase['reviseNotes'] = [[
                'tanggal' => null,
                'alasan' => $phase['reviseNotes'],
                'user' => null,
            ]];
            return;
        }

        if (!is_array($phase['reviseNotes'])) {
            $phase['reviseNotes'] = [];
        }
    }

    protected function appendReviseLog(array $phases, int $index, string $reason): array
    {
        if (!isset($phases[$index])) return $phases;

        $this->ensureReviseNotesArray($phases[$index]);

        $user = auth()->user();
        $phases[$index]['reviseNotes'][] = [
            'tanggal' => now()->format(DATE_ATOM),
            'alasan' => $reason,
            'user' => $user?->name ?? $user?->email ?? (string)$user?->id ?? 'system',
        ];

        return $phases;
    }

    protected function tryApplyPendingChange(Get $get, Set $set): void
    {
        if ($this->pendingIndex === null || $this->pendingNewStatus === null) return;

        $idx = $this->pendingIndex;
        $newStatus = $this->pendingNewStatus;

        $reason = trim((string)($get("form_data.phases.$idx.reviseNotes") ?? ''));
        if ($reason === '') return;

        $this->pendingIndex = null;
        $this->pendingNewStatus = null;
        $this->editedIndex = null;

        $this->onPhaseStatusChange($idx, $newStatus, $get, $set);
    }

    /** Ubah status phase (cascade), TANPA menyentuh updatedAt */
    protected function onPhaseStatusChange(int $index, string $newStatus, Get $get, Set $set): void
    {
        $record = $this->getRecord()->refresh();
        $before = $record->form_data ?? [];

        $dbPhases = $record->form_data['phases'] ?? [];
        if (!array_key_exists($index, $dbPhases)) {
            FNotification::make()->title('Phase tidak ditemukan')->danger()->send();
            return;
        }

        $oldStatus = $dbPhases[$index]['status'] ?? null;
        $phaseName = $dbPhases[$index]['name'] ?? ('Phase #' . ($index + 1));

        // Wajib revise notes jika dari finish -> pending/progress
        if ($this->requiresReviseNotes($oldStatus, $newStatus)) {
            $this->editedIndex = $index;

            $reason = trim((string)($get("form_data.phases.$index.reviseNotes") ?? ''));
            if ($reason === '') {
                $this->pendingIndex = $index;
                $this->pendingNewStatus = $newStatus;

                FNotification::make()
                    ->title('Mohon isi alasan revisi')
                    ->body('Perubahan dari Finished ke Pending/On Progress wajib menyertakan "Revise Notes".')
                    ->warning()
                    ->send();
                return;
            }

            $dbPhases = $this->appendReviseLog($dbPhases, $index, $reason);
            $this->accumulatedChanges[] = [
                'scope' => 'phase',
                'phase' => $phaseName,
                'index' => $index,
                'field' => 'reviseNotes',
                'from' => null,
                'to' => $reason,
            ];
            $set("form_data.phases.$index.reviseNotes", null);

            $this->editedIndex = null;
            $this->pendingIndex = null;
            $this->pendingNewStatus = null;
        } else {
            $this->editedIndex = null;
            $this->pendingIndex = null;
            $this->pendingNewStatus = null;
        }

        // Cascade status seperti semula
        $phases = $this->applyRules($dbPhases, $index, $newStatus);

        // diff reaktif (untuk notifikasi UI)
        $after = $before;
        $after['phases'] = $this->sanitizePhases($phases);
        $diff = $this->diffFormData($before, $after);
        $this->accumulatedChanges = array_merge($this->accumulatedChanges, $diff);

        // update badge/status di UI
        $phases = $this->sanitizePhases($phases);
        foreach ($phases as $i => $p) {
            if (array_key_exists('status', $p)) {
                $set("form_data.phases.$i.status", $p['status']);
            }
        }

        // simpan perubahan status (TANPA updatedAt)
        $this->savePhases($phases);

        // sesuaikan status global berdasar fase "Closed"
        $this->syncGlobalStatusFromClosedPhase($this->getRecord(), $phases);

        // auto-scroll bar tabs ke tab aktif
        $this->dispatch('scroll-active-tab');

        // notify
        $statusChanged = $this->phasesStatusChanged($dbPhases, $phases);
        $record->refresh();

        if ($statusChanged) {
            FNotification::make()
                ->title("{$record->recruitmentRequest->title} Berhasil Diperbaharui")
                ->body("Perubahan status menjadi {$newStatus}")
                ->success()
                ->send();

            $actor = auth()->user();
            $recipients = self::getRecipients($actor);

            Notify::recruitmentActivity(
                recipients: $recipients,
                recruitmentId: (string)$record->getKey(),
                action: 'phase_status_changed',
                context: [
                    'from' => $oldStatus,
                    'to' => $newStatus,
                    'title' => $record->title,
                    'phase' => $phaseName,
                    'index' => $index,
                ],
                actorId: (string)($actor->id ?? 'system'),
                actorName: $actor->name ?? 'System',
                department: $this->department,
            );
        }
    }

    protected function phasesStatusChanged(array $beforePhases, array $afterPhases): bool
    {
        $max = max(count($beforePhases), count($afterPhases));
        for ($i = 0; $i < $max; $i++) {
            $old = $beforePhases[$i]['status'] ?? null;
            $new = $afterPhases[$i]['status'] ?? null;
            if ($old !== $new) return true;
        }
        return false;
    }

    protected function relatedDepartmentIds(): array
    {
        return array_values(array_filter([$this->hrDepartmentId, $this->departmentId]));
    }

    protected function getRecipients($actor): Collection
    {
        $deptIds = $this->relatedDepartmentIds();

        return User::query()
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['Manager', 'Asmen', 'Team Leader', 'SPV', 'SU']))
            ->when(!empty($deptIds), fn($q) => $q->whereHas('departments', fn($d) => $d->whereIn('departments.id', $deptIds)))
            ->when($actor, fn($q) => $q->where('id', '!=', $actor->getKey()))
            ->get();
    }

    protected function getRecipientsEmail($actor): array
    {
        $deptIds = $this->relatedDepartmentIds();

        return User::query()
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['Manager', 'Asmen', 'Team Leader', 'SPV', 'SU']))
            ->when(!empty($deptIds), fn($q) => $q->whereHas('departments', fn($d) => $d->whereIn('departments.id', $deptIds)))
            ->when($actor, fn($q) => $q->where('id', '!=', $actor->getKey()))
            ->pluck('email')
            ->filter(fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->toArray();
    }

    protected function sanitizePhases(array $phases): array
    {
        foreach ($phases as &$phase) {
            if (is_array($phase) && array_key_exists('form_data', $phase)) {
                unset($phase['form_data']);
            }
        }
        return $phases;
    }

    /** Cascade rules seperti semula */
    protected function applyRules(array $phases, int $index, string $newStatus): array
    {
        $total = count($phases);
        if ($total === 0 || !isset($phases[$index])) {
            return $phases;
        }

        if ($newStatus === 'progress') {
            for ($i = 0; $i < $total; $i++) {
                if ($i < $index) {
                    $phases[$i]['status'] = 'finish';
                } elseif ($i === $index) {
                    $phases[$i]['status'] = 'progress';
                } else {
                    $phases[$i]['status'] = 'pending';
                }
            }
            return $phases;
        }

        if ($newStatus === 'finish') {
            for ($i = 0; $i <= $index; $i++) {
                $phases[$i]['status'] = 'finish';
            }
            if (isset($phases[$index + 1])) {
                $phases[$index + 1]['status'] = 'progress';
                for ($j = $index + 2; $j < $total; $j++) {
                    $phases[$j]['status'] = 'pending';
                }
            }
            return $phases;
        }

        if ($newStatus === 'pending') {
            for ($i = 0; $i < $total; $i++) {
                $phases[$i]['status'] = 'pending';
            }

            $prev = max(0, $index - 1);
            for ($i = 0; $i < $prev; $i++) {
                $phases[$i]['status'] = 'finish';
            }
            $phases[$prev]['status'] = 'progress';

            return $phases;
        }

        return $phases;
    }

    protected function savePhases(array $phases, bool $showToast = false): bool
    {
        $record = $this->getRecord()->refresh();

        $newPhases = $this->sanitizePhases($phases);
        $data = $record->form_data ?? [];
        $oldPhases = $this->sanitizePhases($data['phases'] ?? []);

        if (json_encode($newPhases) === json_encode($oldPhases)) {
            return false;
        }

        DB::transaction(function () use ($record, $data, $newPhases) {
            $payload = $data;
            $payload['phases'] = $newPhases;
            $record->forceFill(['form_data' => $payload])->save();
        });

        $record->refresh();
        $this->fillForm();

        if ($showToast) {
            FNotification::make()
                ->title('Phase updated')
                ->success()
                ->send();
        }

        return true;
    }

    protected function normalizeForDiff($v)
    {
        if (is_null($v)) return null;
        if (is_bool($v)) return $v ? '1' : '0';
        if (is_int($v) || is_float($v)) return (string)$v;
        if (is_string($v)) return $v;
        return json_encode($v);
    }

    protected function diffFormData(array $before, array $after): array
    {
        $changes = [];

        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
        foreach ($keys as $k) {
            if ($k === 'phases') continue;
            if (in_array($k, $this->diffExcludeKeys, true)) continue;

            $old = $before[$k] ?? null;
            $new = $after[$k] ?? null;

            if ($this->normalizeForDiff($old) !== $this->normalizeForDiff($new)) {
                $changes[] = [
                    'scope' => 'form_data',
                    'phase' => null,
                    'index' => null,
                    'field' => $k,
                    'from' => $old,
                    'to' => $new,
                ];
            }
        }

        $bp = $before['phases'] ?? [];
        $ap = $after['phases'] ?? [];
        $max = max(count($bp), count($ap));

        for ($i = 0; $i < $max; $i++) {
            $bpi = $bp[$i] ?? [];
            $api = $ap[$i] ?? [];
            $phaseName = $api['name'] ?? $bpi['name'] ?? ('Phase #' . ($i + 1));

            $kset = array_unique(array_merge(array_keys($bpi), array_keys($api)));
            foreach ($kset as $field) {
                if (in_array($field, $this->diffExcludeKeys, true)) continue;

                $old = $bpi[$field] ?? null;
                $new = $api[$field] ?? null;

                if ($this->normalizeForDiff($old) !== $this->normalizeForDiff($new)) {
                    $changes[] = [
                        'scope' => 'phase',
                        'phase' => $phaseName,
                        'index' => $i,
                        'field' => $field,
                        'from' => $old,
                        'to' => $new,
                    ];
                }
            }
        }

        return $changes;
    }

    /** SUBMIT: updateAt hanya untuk phase yang field-nya berubah (non-status) */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->refresh();
        $before = $record->form_data ?? [];

        $db = $record->form_data ?? [];
        $input = $data['form_data'] ?? [];

        // Commit reviseNotes tertunda (jika ada) → TIDAK memicu updatedAt
        if ($this->pendingIndex !== null && $this->pendingNewStatus !== null) {
            $idx = $this->pendingIndex;
            $reason = trim((string)($input['phases'][$idx]['reviseNotes'] ?? ''));

            if ($reason !== '' && isset(($db['phases'] ?? [])[$idx])) {
                $db['phases'] = $this->appendReviseLog($db['phases'], $idx, $reason);
                $db['phases'] = $this->applyRules($db['phases'], $idx, $this->pendingNewStatus);

                $this->pendingIndex = null;
                $this->pendingNewStatus = null;
                $this->editedIndex = null;
            }
        }

        // Merge input per-phase (kecuali status & reviseNotes)
        $changedIndices = [];
        if (isset($input['phases']) && is_array($input['phases'])) {
            $db['phases'] = $db['phases'] ?? [];

            foreach ($input['phases'] as $i => $phaseInput) {
                if (!isset($db['phases'][$i])) $db['phases'][$i] = [];

                if (is_array($phaseInput) && array_key_exists('form_data', $phaseInput)) {
                    unset($phaseInput['form_data']);
                }

                $changed = false;
                foreach ($phaseInput as $k => $v) {
                    if (in_array($k, ['status', 'reviseNotes'], true)) continue; // tidak trigger updatedAt
                    if ($k === 'finisihed') $k = 'finished'; // normalisasi typo

                    $old = $db['phases'][$i][$k] ?? null;

                    $isDiff = is_array($old) || is_array($v)
                        ? json_encode($old) !== json_encode($v)
                        : $this->normalizeForDiff($old) !== $this->normalizeForDiff($v);

                    if ($isDiff) {
                        $changed = true;
                        $db['phases'][$i][$k] = $v;

                        // cleanup typo
                        if ($k === 'finished' && isset($db['phases'][$i]['finisihed'])) {
                            unset($db['phases'][$i]['finisihed']);
                        }
                    }
                }

                if ($changed) {
                    $changedIndices[] = (int)$i; // hanya index yang field-nya berubah
                }

                // cleanup sisa typo
                if (isset($db['phases'][$i]['finisihed'])) {
                    unset($db['phases'][$i]['finisihed']);
                }
            }
        }

        foreach ($input as $k => $v) {
            if ($k === 'phases') continue;
            $db[$k] = $v;
        }

        $db['phases'] = $this->sanitizePhases($db['phases'] ?? []);

        // KUNCI: set updatedAt HANYA utk phase yang field-nya berubah
        if (!empty($changedIndices)) {
            $now = $this->nowAtom();
            foreach (array_unique($changedIndices) as $idx) {
                if (isset($db['phases'][$idx])) {
                    $db['phases'][$idx]['updatedAt'] = $now;
                }
            }
        }

        // Diff (setelah updatedAt diset) untuk notifikasi
        $this->latestChanges = $this->diffFormData($before, $db);

        // Simpan
        $record->form_data = $db;
        $record->save();

        // Sinkronkan status global berdasar fase "Closed"
        $this->syncGlobalStatusFromClosedPhase($record, $db['phases'] ?? []);

        return $record;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord()->refresh();

        FNotification::make()
            ->title("Tahap {$record->recruitmentRequest->title} berhasil diperbarui")
            ->success()
            ->send();

        $actor = auth()->user();
        $recipients = self::getRecipients($actor);

        $allChanges = array_merge($this->accumulatedChanges, $this->latestChanges);

        $changesPayload = array_map(static function (array $c) {
            return [
                'updating' => 'update',
                'scope' => $c['scope'],
                'phase' => $c['phase'],
                'index' => $c['index'],
                'field' => $c['field'],
                'oldValue' => $c['from'],
                'newValue' => $c['to'],
            ];
        }, $allChanges);

        Notify::recruitmentActivity(
            recipients: $recipients,
            recruitmentId: (string)$record->getKey(),
            action: 'detail_change',
            context: $changesPayload,
            actorId: (string)($actor->id ?? 'system'),
            actorName: $actor->name ?? 'System',
            department: $this->department,
        );

        // Email jika ada reviseNotes
        $reviseItems = array_values(array_filter(
            $changesPayload,
            static fn($c) => isset($c['field']) && strtolower((string)$c['field']) === 'revisenotes'
        ));

        $revise = !empty($reviseItems)
            ? $reviseItems[array_key_last($reviseItems)]
            : null;

        if ($revise) {
            $phaseName = $revise['phase'] ?? '-';

            $emails = array_values(array_unique(array_filter(
                self::getRecipientsEmail($actor),
                fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL)
            )));

            if (!empty($emails)) {
                $subject = sprintf('Perubahan Tahap Proses Perekrutan – %s', $this->department);
                $message = sprintf(
                    'Kami informasikan bahwa %s telah melakukan pengunduran tahap ke "%s" pada proses perekrutan di departemen %s.',
                    $actor->name ?? 'System',
                    $phaseName,
                    $this->department
                );

                foreach ($emails as $recipient) {
                    try {
                        Emailer::notify(
                            to: $recipient,
                            subject: $subject,
                            message: $message,
                        );
                    } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
                        Log::warning("Gagal kirim ke {$recipient}: " . $e->getMessage());
                        continue;
                    }
                }
            }
        }

        // reset buffer perubahan
        $this->accumulatedChanges = [];
        $this->latestChanges = [];

        // pastikan bar tab mengikuti tab aktif setelah save
        $this->dispatch('scroll-active-tab');

        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    // =========================
    // Helpers tambahan
    // =========================

    protected function nowAtom(): string
    {
        $tz = config('app.timezone', 'Asia/Jakarta');
        return now($tz)->toAtomString();
    }

    protected function lastPhaseIsFinished(array $phases): bool
    {
        if (empty($phases)) return false;
        $last = $phases[array_key_last($phases)] ?? [];
        return ($last['status'] ?? null) === 'finish';
    }

    /**
     * Sinkronkan status global model & request induk berdasar fase "Closed" (phase terakhir):
     * - Jika Closed 'finish'  => set keduanya 'finish'
     * - Jika Closed tidak 'finish' => set keduanya 'progress'
     */
    protected function syncGlobalStatusFromClosedPhase(Model $record, array $phases): void
    {
        $phaseStatus = $this->lastPhaseIsFinished($phases) ? 'finish' : 'progress';

        DB::transaction(function () use ($record, $phaseStatus) {
            $record->forceFill(['status' => $phaseStatus])->save();

            if ($record->recruitmentRequest) {
                $record->recruitmentRequest->forceFill(['status' => $phaseStatus])->save();
            }
        });
    }

    /**
     * Pilih index tab:
     * 1) pertama yang 'progress'
     * 2) jika tidak ada, pertama yang 'pending'
     * 3) jika tetap tidak ada, 0
     */
    protected function getActivePhaseIndex(): int
    {
        $phases = (array)($this->getRecord()?->form_data['phases'] ?? []);
        foreach ($phases as $i => $p) {
            if (($p['status'] ?? null) === 'progress') return $i + 1;
        }
        foreach ($phases as $i => $p) {
            if (($p['status'] ?? null) === 'pending') return $i;
        }
        return 0;
    }
}
