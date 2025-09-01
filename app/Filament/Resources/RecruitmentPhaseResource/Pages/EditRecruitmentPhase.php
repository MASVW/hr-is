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

        $this->department   = $r?->recruitmentRequest?->department?->name ?? null;
        $this->departmentId = $r?->recruitmentRequest?->department?->id   ?? null;
        $this->hrDepartmentId = Department::where('name', 'HUMAN RESOURCE')->value('id');
    }

    public function form(Form $form): Form
    {
        // Tambahkan 'pending' supaya mundur fase bisa dilakukan eksplisit
        $statusOption = [
            'finish'   => 'Finished',
            'progress' => 'On Progress',
            'pending'  => 'Pending',
        ];

        return $form
            ->schema([
                TextInput::make('status') // status kolom model utama (bukan per-fase)
                ->disabled()
                    ->dehydrated(false)
                    ->afterStateHydrated(fn ($component) => $component->state(fn ($state) => ucfirst((string) $state)))
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
                            ->tabs(fn (Get $get) => $this->buildDynamicPhaseTabs($get, $statusOption))
                            ->reactive(),
                    ]),
            ]);
    }

    /**
     * Bangun tabs fase secara dinamis dari form_data.phases
     */
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
                        'finish'   => 'primary',
                        default    => 'gray',
                    };
                })
                ->schema($this->makePhaseFields($idx, (array) $phase, $statusOption))
                ->dehydrated(true);
        }

        return $tabs;
    }

    /**
     * Tentukan field schema untuk 1 fase secara dinamis
     */
    protected function makePhaseFields(int $index, array $phase, array $statusOption): array
    {
        $fields = [];

        // --- Field STATUS (selalu ada) ---
        $fields[] = Select::make('status')
            ->dehydrated(false) // status diproses via onPhaseStatusChange
            ->live()
            ->reactive()
            ->options($statusOption)
            ->afterStateUpdated(function (string $state, Set $set, Get $get) use ($index) {
                $this->onPhaseStatusChange($index, $state, $get, $set);
            });

        // --- Field umum ---
        $fields[] = Textarea::make('note')->label('Note')->columnSpanFull();

        // --- Revise Notes (muncul hanya saat perlu) ---
        $fields[] = Textarea::make('reviseNotes')
            ->dehydrated(true) // kita tangkap reason di handleRecordUpdate/onPhaseStatusChange
            ->afterStateHydrated(fn ($component) => $component->state(''))
            ->label('Revise Notes')
            ->helperText('Wajib diisi saat mengubah dari Finished ke Pending / On Progress.')
            ->debounce(300)
            ->live()
            ->reactive()
            ->required(fn () => $this->editedIndex === $index && $this->pendingNewStatus !== null)
            ->hidden(fn () => $this->editedIndex !== $index)
            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                if (filled($state)) {
                    $this->tryApplyPendingChange($get, $set);
                }
            });

        // --- Field lainnya dinamis dari key yang ada ---
        // abaikan beberapa key yang tidak diedit langsung atau sudah ditangani khusus
        $ignored = ['name', 'status', 'note', 'updatedAt', 'reviseNotes', 'form_data'];

        foreach ($phase as $key => $value) {
            if (in_array($key, $ignored, true)) {
                continue;
            }

            // case khusus yang sering muncul
            if ($key === 'isApproved') {
                $fields[] = Toggle::make($key)->label('Approved')->dehydrated(true);
                continue;
            }

            if ($key === 'candidate' && is_array($value)) {
                // kandidat array → repeater (name, position, onBoardingDate)
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

            if (in_array($key, ['finished', 'finisihed'], true)) {
                // typo kompatibilitas: render ke 'finished', simpan tetap dibersihkan di handleRecordUpdate
                $fields[] = TextInput::make('finished')
                    ->label('Finished')
                    ->numeric()
                    ->dehydrated(true);
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

            // string default → text / textarea (panjang dipilih otomatis)
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

            // fallback: tampilkan sebagai text (json)
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
        return str($key)
            ->replace('_', ' ')
            ->replace('-', ' ')
            ->replaceMatches('/([A-Z])/', ' $1')
            ->trim()
            ->headline()
            ->toString();
    }

    protected function isNumericLike($v, string $key): bool
    {
        // beberapa key umum yang numeric
        $knownNumeric = [
            'totalCV','approvedCV','checked','interviewed',
            'candidate','passed','agreed','offered','onboarded','hasChecked',
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
                'alasan'  => $phase['reviseNotes'],
                'user'    => null,
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
            'alasan'  => $reason,
            'user'    => $user?->name ?? $user?->email ?? (string) $user?->id ?? 'system',
        ];

        return $phases;
    }

    protected function tryApplyPendingChange(Get $get, Set $set): void
    {
        if ($this->pendingIndex === null || $this->pendingNewStatus === null) return;

        $idx       = $this->pendingIndex;
        $newStatus = $this->pendingNewStatus;

        $reason = trim((string) ($get("form_data.phases.$idx.reviseNotes") ?? ''));
        if ($reason === '') return;

        $this->pendingIndex     = null;
        $this->pendingNewStatus = null;
        $this->editedIndex      = null;

        $this->onPhaseStatusChange($idx, $newStatus, $get, $set);
    }

    protected function onPhaseStatusChange(int $index, string $newStatus, Get $get, Set $set): void
    {
        $record = $this->getRecord()->refresh();
        $before = $record->form_data ?? [];

        $dbPhases = $record->form_data['phases'] ?? [];
        if (! array_key_exists($index, $dbPhases)) {
            FNotification::make()->title('Phase tidak ditemukan')->danger()->send();
            return;
        }

        $oldStatus = $dbPhases[$index]['status'] ?? null;
        $phaseName = $dbPhases[$index]['name'] ?? ('Phase #'.($index + 1));

        if ($this->requiresReviseNotes($oldStatus, $newStatus)) {
            $this->editedIndex = $index;

            $reason = trim((string) ($get("form_data.phases.$index.reviseNotes") ?? ''));
            if ($reason === '') {
                $this->pendingIndex     = $index;
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
                'from'  => null,
                'to'    => $reason,
            ];
            $set("form_data.phases.$index.reviseNotes", null);

            $this->editedIndex      = null;
            $this->pendingIndex     = null;
            $this->pendingNewStatus = null;
        } else {
            $this->editedIndex      = null;
            $this->pendingIndex     = null;
            $this->pendingNewStatus = null;
        }

        $phases = $this->applyRules($dbPhases, $index, $newStatus);

        // diff reaktif
        $after = $before;
        $after['phases'] = $this->sanitizePhases($phases);
        $diff = $this->diffFormData($before, $after);
        $this->accumulatedChanges = array_merge($this->accumulatedChanges, $diff);

        // set state mini (agar badge/indikator berubah langsung)
        $phases = $this->sanitizePhases($phases);
        foreach ($phases as $i => $p) {
            if (array_key_exists('status', $p)) {
                $set("form_data.phases.$i.status", $p['status']);
            }
        }

        // simpan
        $this->savePhases($phases);

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
                recruitmentId: (string) $record->getKey(),
                action: 'phase_status_changed',
                context: [
                    'from'  => $oldStatus,
                    'to'    => $newStatus,
                    'title' => $record->title,
                    'phase' => $phaseName,
                    'index' => $index,
                ],
                actorId: (string) ($actor->id ?? 'system'),
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
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Manager', 'Asmen']))
            ->when(!empty($deptIds), fn ($q) => $q->whereHas('departments', fn ($d) => $d->whereIn('departments.id', $deptIds)))
            ->when($actor, fn ($q) => $q->where('id', '!=', $actor->getKey()))
            ->get();
    }

    protected function getRecipientsEmail($actor): array
    {
        $deptIds = $this->relatedDepartmentIds();

        return User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Manager', 'Asmen']))
            ->when(!empty($deptIds), fn ($q) => $q->whereHas('departments', fn ($d) => $d->whereIn('departments.id', $deptIds)))
            ->when($actor, fn ($q) => $q->where('id', '!=', $actor->getKey()))
            ->pluck('email')
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
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
        $data      = $record->form_data ?? [];
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
        if (is_int($v) || is_float($v)) return (string) $v;
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
                    'from'  => $old,
                    'to'    => $new,
                ];
            }
        }

        $bp = $before['phases'] ?? [];
        $ap = $after['phases'] ?? [];
        $max = max(count($bp), count($ap));

        for ($i = 0; $i < $max; $i++) {
            $bpi = $bp[$i] ?? [];
            $api = $ap[$i] ?? [];
            $phaseName = $api['name'] ?? $bpi['name'] ?? ('Phase #'.($i + 1));

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
                        'from'  => $old,
                        'to'    => $new,
                    ];
                }
            }
        }

        return $changes;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->refresh();
        $before = $record->form_data ?? [];

        $db    = $record->form_data ?? [];
        $input = $data['form_data'] ?? [];

        // commit reviseNotes tertunda (jika ada)
        if ($this->pendingIndex !== null && $this->pendingNewStatus !== null) {
            $idx    = $this->pendingIndex;
            $reason = trim((string) ($input['phases'][$idx]['reviseNotes'] ?? ''));

            if ($reason !== '' && isset(($db['phases'] ?? [])[$idx])) {
                $db['phases'] = $this->appendReviseLog($db['phases'], $idx, $reason);
                $db['phases'] = $this->applyRules($db['phases'], $idx, $this->pendingNewStatus);

                $this->pendingIndex     = null;
                $this->pendingNewStatus = null;
                $this->editedIndex      = null;
            }
        }

        // merge data per-fase selain status & reviseNotes (status diatur rules)
        if (isset($input['phases']) && is_array($input['phases'])) {
            $db['phases'] = $db['phases'] ?? [];

            foreach ($input['phases'] as $i => $phaseInput) {
                if (!isset($db['phases'][$i])) $db['phases'][$i] = [];

                if (is_array($phaseInput) && array_key_exists('form_data', $phaseInput)) {
                    unset($phaseInput['form_data']);
                }

                foreach ($phaseInput as $k => $v) {
                    if (in_array($k, ['status', 'reviseNotes'], true)) continue;
                    // normalisasi typo 'finisihed' → 'finished'
                    if ($k === 'finisihed') $k = 'finished';
                    $db['phases'][$i][$k] = $v;
                }
            }
        }

        foreach ($input as $k => $v) {
            if ($k === 'phases') continue;
            $db[$k] = $v;
        }

        $db['phases'] = $this->sanitizePhases($db['phases'] ?? []);

        $this->latestChanges = $this->diffFormData($before, $db);

        $record->form_data = $db;
        $record->save();

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
                'scope'    => $c['scope'],
                'phase'    => $c['phase'],
                'index'    => $c['index'],
                'field'    => $c['field'],
                'oldValue' => $c['from'],
                'newValue' => $c['to'],
            ];
        }, $allChanges);

        Notify::recruitmentActivity(
            recipients:    $recipients,
            recruitmentId: (string) $record->getKey(),
            action:        'detail_change',
            context:       $changesPayload,
            actorId:       (string) ($actor->id ?? 'system'),
            actorName:     $actor->name ?? 'System',
            department:    $this->department,
        );

        // Kirim email khusus jika ada reviseNotes
        $reviseItems = array_values(array_filter(
            $changesPayload,
            static fn ($c) => isset($c['field']) && strtolower((string)$c['field']) === 'revisenotes'
        ));

        $revise = !empty($reviseItems)
            ? $reviseItems[array_key_last($reviseItems)]
            : null;

        if ($revise) {
            $phaseName = $revise['phase'] ?? '-';

            $emails = array_values(array_unique(array_filter(
                self::getRecipientsEmail($actor),
                fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)
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

        // reset buffer
        $this->accumulatedChanges = [];
        $this->latestChanges = [];

        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
