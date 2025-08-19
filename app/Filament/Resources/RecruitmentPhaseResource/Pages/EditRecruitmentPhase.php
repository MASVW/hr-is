<?php

namespace App\Filament\Resources\RecruitmentPhaseResource\Pages;

use App\Filament\Resources\RecruitmentPhaseResource;
use App\Models\Department;
use App\Models\User;
use App\Support\Emailer;
use App\Support\Notify;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

    public $department;
    public $departmentId;
    public $hrDepartmentId;

    public array $accumulatedChanges = [];

    public array $latestChanges = [];

    /** Key yang tidak disertakan dalam diff */
    protected array $diffExcludeKeys = ['form_data', 'status'];

    /** Pastikan department tersedia lebih awal */
    protected function beforeFill(): void
    {
        $r = $this->getRecord();
        $this->department = $r?->recruitmentRequest?->department?->name ?? null;
        $this->departmentId = $r?->recruitmentRequest?->department?->id ?? null;
        $this->hrDepartmentId =  Department::where('name', 'HUMAN RESOURCE')->first()->id;
    }

    public function form(Form $form): Form
    {
        $statusOption = [
            'finish'   => 'Finished',
            'progress' => 'On Progress',
        ];

        return $form
            ->schema([
                TextInput::make('status')
                    ->disabled()
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($component) {
                        $component->state(fn ($state) => ucfirst($state));
                    })
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
                                return "Dalam Tahap {$phase['name']}" ?? '-';
                            }
                        }
                        return '-';
                    })
                    ->statePath('form_data')
                    ->schema([
                        Tabs::make('Phases')
                            ->tabs([
                                Tabs\Tab::make('CV Collection')
                                    ->statePath('phases.2')
                                    ->icon('heroicon-o-document-text')
                                    ->badge(fn ($record) => ucfirst($record->form_data['phases'][2]['status'] ?? ''))
                                    ->badgeColor(fn ($record) => match ($record->form_data['phases'][2]['status'] ?? null) {
                                        'progress' => 'success',
                                        'finish' => 'primary',
                                        default => 'gray'
                                    })
                                    ->schema([
                                        Select::make('status')
                                            ->dehydrated(false)
                                            ->live()
                                            ->reactive()
                                            ->options($statusOption)
                                            ->afterStateUpdated(function (string $state, Set $set, Get $get) {
                                                $this->onPhaseStatusChange(2, $state, $get, $set);
                                            }),
                                        TextInput::make('totalCV')
                                            ->label('Curriculum Vitae diterima')
                                            ->numeric(),
                                        Textarea::make('note'),
                                        Textarea::make('reviseNotes')
                                            ->dehydrated(true)
                                            ->afterStateHydrated(fn ($component) => $component->state(''))
                                            ->label('Revise Notes')
                                            ->helperText('Wajib diisi saat mengubah dari Finished ke Pending / On Progress.')
                                            ->debounce(300)
                                            ->live()
                                            ->reactive()
                                            ->required(fn () => $this->editedIndex === 2 && $this->pendingNewStatus !== null)
                                            ->hidden(fn () => $this->editedIndex !== 2)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if (filled($state)) {
                                                    $this->tryApplyPendingChange($get, $set);
                                                }
                                            })
                                    ])
                                    ->dehydrated(true),

                                Tabs\Tab::make('CV Screening')
                                    ->statePath('phases.3')
                                    ->icon('heroicon-o-magnifying-glass')
                                    ->badge(fn ($record) => ucfirst($record->form_data['phases'][3]['status'] ?? ''))
                                    ->badgeColor(fn ($record) => match ($record->form_data['phases'][3]['status'] ?? null) {
                                        'progress' => 'success',
                                        'finish' => 'primary',
                                        default => 'gray'
                                    })
                                    ->schema([
                                        Select::make('status')
                                            ->dehydrated(false)
                                            ->live()
                                            ->reactive()
                                            ->options($statusOption)
                                            ->afterStateUpdated(function (string $state, Set $set, Get $get) {
                                                $this->onPhaseStatusChange(3, $state, $get, $set);
                                            }),
                                        TextInput::make('approvedCV')
                                            ->label('Curriculum Vitae Diterima')
                                            ->numeric(),
                                        Textarea::make('note'),
                                        Textarea::make('reviseNotes')
                                            ->dehydrated(true)
                                            ->afterStateHydrated(fn ($component) => $component->state(''))
                                            ->label('Revise Notes')
                                            ->helperText('Wajib diisi saat mengubah dari Finished ke Pending / On Progress.')
                                            ->debounce(300)
                                            ->live()
                                            ->reactive()
                                            ->required(fn () => $this->editedIndex === 3 && $this->pendingNewStatus !== null)
                                            ->hidden(fn () => $this->editedIndex !== 3)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if (filled($state)) {
                                                    $this->tryApplyPendingChange($get, $set);
                                                }
                                            })
                                    ])
                                    ->dehydrated(true),

                                Tabs\Tab::make('Check Background')
                                    ->statePath('phases.4')
                                    ->icon('heroicon-o-shield-check')
                                    ->badge(fn ($record) => ucfirst($record->form_data['phases'][4]['status'] ?? ''))
                                    ->badgeColor(fn ($record) => match ($record->form_data['phases'][4]['status'] ?? null) {
                                        'progress' => 'success',
                                        'finish' => 'primary',
                                        default => 'gray'
                                    })
                                    ->schema([
                                        Select::make('status')
                                            ->dehydrated(false)
                                            ->live()
                                            ->reactive()
                                            ->options($statusOption)
                                            ->afterStateUpdated(function (string $state, Set $set, Get $get) {
                                                $this->onPhaseStatusChange(4, $state, $get, $set);
                                            }),
                                        TextInput::make('candidate')->numeric(),
                                        TextInput::make('checked')->numeric(),
                                        TextInput::make('passed')->numeric(),
                                        Textarea::make('note'),
                                        Textarea::make('reviseNotes')
                                            ->dehydrated(true)
                                            ->afterStateHydrated(fn ($component) => $component->state(''))
                                            ->label('Revise Notes')
                                            ->helperText('Wajib diisi saat mengubah dari Finished ke Pending / On Progress.')
                                            ->debounce(300)
                                            ->live()
                                            ->reactive()
                                            ->required(fn () => $this->editedIndex === 4 && $this->pendingNewStatus !== null)
                                            ->hidden(fn () => $this->editedIndex !== 4)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if (filled($state)) {
                                                    $this->tryApplyPendingChange($get, $set);
                                                }
                                            })
                                    ])
                                    ->dehydrated(true),

                                Tabs\Tab::make('Psychology Assessment')
                                    ->statePath('phases.5')
                                    ->icon('heroicon-o-clipboard-document-check')
                                    ->badge(fn ($record) => ucfirst($record->form_data['phases'][5]['status'] ?? ''))
                                    ->badgeColor(fn ($record) => match ($record->form_data['phases'][5]['status'] ?? null) {
                                        'progress' => 'success',
                                        'finish' => 'primary',
                                        default => 'gray'
                                    })
                                    ->schema([
                                        Select::make('status')
                                            ->dehydrated(false)
                                            ->live()
                                            ->reactive()
                                            ->options($statusOption)
                                            ->afterStateUpdated(function (string $state, Set $set, Get $get) {
                                                $this->onPhaseStatusChange(5, $state, $get, $set);
                                            }),
                                        TextInput::make('candidate')->numeric(),
                                        TextInput::make('finished')->numeric(),
                                        TextInput::make('passed')->numeric(),
                                        Textarea::make('note'),
                                        Textarea::make('reviseNotes')
                                            ->dehydrated(true)
                                            ->afterStateHydrated(fn ($component) => $component->state(''))
                                            ->label('Revise Notes')
                                            ->helperText('Wajib diisi saat mengubah dari Finished ke Pending / On Progress.')
                                            ->debounce(300)
                                            ->live()
                                            ->reactive()
                                            ->required(fn () => $this->editedIndex === 5 && $this->pendingNewStatus !== null)
                                            ->hidden(fn () => $this->editedIndex !== 5)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if (filled($state)) {
                                                    $this->tryApplyPendingChange($get, $set);
                                                }
                                            })
                                    ])->dehydrated(true),

                                Tabs\Tab::make('HRD Interview')
                                    ->statePath('phases.6')
                                    ->icon('heroicon-o-chat-bubble-left-right')
                                    ->badge(fn ($record) => ucfirst($record->form_data['phases'][6]['status'] ?? ''))
                                    ->badgeColor(fn ($record) => match ($record->form_data['phases'][6]['status'] ?? null) {
                                        'progress' => 'success',
                                        'finish' => 'primary',
                                        default => 'gray'
                                    })
                                    ->schema([
                                        Select::make('status')
                                            ->dehydrated(false)
                                            ->live()
                                            ->reactive()
                                            ->options($statusOption)
                                            ->afterStateUpdated(function (string $state, Set $set, Get $get) {
                                                $this->onPhaseStatusChange(6, $state, $get, $set);
                                            }),
                                        TextInput::make('interviewed')->numeric(),
                                        TextInput::make('candidate')->numeric(),
                                        TextInput::make('passed')->numeric(),
                                        Textarea::make('note'),
                                        Textarea::make('reviseNotes')
                                            ->dehydrated(true)
                                            ->afterStateHydrated(fn ($component) => $component->state(''))
                                            ->label('Revise Notes')
                                            ->helperText('Wajib diisi saat mengubah dari Finished ke Pending / On Progress.')
                                            ->debounce(300)
                                            ->live()
                                            ->reactive()
                                            ->required(fn () => $this->editedIndex === 6 && $this->pendingNewStatus !== null)
                                            ->hidden(fn () => $this->editedIndex !== 6)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if (filled($state)) {
                                                    $this->tryApplyPendingChange($get, $set);
                                                }
                                            })
                                    ])
                                    ->dehydrated(true),

                                Tabs\Tab::make('Interview with User')
                                    ->statePath('phases.7')
                                    ->icon('heroicon-o-user-group')
                                    ->badge(fn ($record) => ucfirst($record->form_data['phases'][7]['status'] ?? ''))
                                    ->badgeColor(fn ($record) => match ($record->form_data['phases'][7]['status'] ?? null) {
                                        'progress' => 'success',
                                        'finish' => 'primary',
                                        default => 'gray'
                                    })
                                    ->schema([
                                        Select::make('status')
                                            ->dehydrated(false)
                                            ->live()
                                            ->reactive()
                                            ->options($statusOption)
                                            ->afterStateUpdated(function (string $state, Set $set, Get $get) {
                                                $this->onPhaseStatusChange(7, $state, $get, $set);
                                            }),
                                        TextInput::make('interviewed')->numeric(),
                                        TextInput::make('candidate')->numeric(),
                                        TextInput::make('passed')->numeric(),
                                        Textarea::make('note'),
                                        Textarea::make('reviseNotes')
                                            ->dehydrated(true)
                                            ->afterStateHydrated(fn ($component) => $component->state(''))
                                            ->label('Revise Notes')
                                            ->helperText('Wajib diisi saat mengubah dari Finished ke Pending / On Progress.')
                                            ->debounce(300)
                                            ->live()
                                            ->reactive()
                                            ->required(fn () => $this->editedIndex === 7 && $this->pendingNewStatus !== null)
                                            ->hidden(fn () => $this->editedIndex !== 7)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if (filled($state)) {
                                                    $this->tryApplyPendingChange($get, $set);
                                                }
                                            })
                                    ])
                                    ->dehydrated(true),

                                Tabs\Tab::make('Offering')
                                    ->statePath('phases.8')
                                    ->icon('heroicon-o-briefcase')
                                    ->badge(fn ($record) => ucfirst($record->form_data['phases'][8]['status'] ?? ''))
                                    ->badgeColor(fn ($record) => match ($record->form_data['phases'][8]['status'] ?? null) {
                                        'progress' => 'success',
                                        'finish' => 'primary',
                                        default => 'gray'
                                    })
                                    ->schema([
                                        Select::make('status')
                                            ->dehydrated(false)
                                            ->live()
                                            ->reactive()
                                            ->options($statusOption)
                                            ->afterStateUpdated(function (string $state, Set $set, Get $get) {
                                                $this->onPhaseStatusChange(8, $state, $get, $set);
                                            }),
                                        TextInput::make('candidate')->numeric(),
                                        TextInput::make('offered')->numeric(),
                                        TextInput::make('agreed')->numeric(),
                                        Textarea::make('note'),
                                        Textarea::make('reviseNotes')
                                            ->dehydrated(true)
                                            ->afterStateHydrated(fn ($component) => $component->state(''))
                                            ->label('Revise Notes')
                                            ->helperText('Wajib diisi saat mengubah dari Finished ke Pending / On Progress.')
                                            ->debounce(300)
                                            ->live()
                                            ->reactive()
                                            ->required(fn () => $this->editedIndex === 8 && $this->pendingNewStatus !== null)
                                            ->hidden(fn () => $this->editedIndex !== 8)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if (filled($state)) {
                                                    $this->tryApplyPendingChange($get, $set);
                                                }
                                            })
                                    ])
                                    ->dehydrated(true),

                                Tabs\Tab::make('Onboarding')
                                    ->statePath('phases.9')
                                    ->icon('heroicon-o-rocket-launch')
                                    ->badge(fn ($record) => ucfirst($record->form_data['phases'][9]['status'] ?? ''))
                                    ->badgeColor(fn ($record) => match ($record->form_data['phases'][9]['status'] ?? null) {
                                        'progress' => 'success',
                                        'finish' => 'primary',
                                        default => 'gray'
                                    })
                                    ->schema([
                                        Select::make('status')
                                            ->dehydrated(false)
                                            ->live()
                                            ->reactive()
                                            ->options($statusOption)
                                            ->afterStateUpdated(function (string $state, Set $set, Get $get) {
                                                $this->onPhaseStatusChange(9, $state, $get, $set);
                                            }),
                                        TextInput::make('onboarded')->numeric(),
                                        Textarea::make('note'),
                                        Textarea::make('reviseNotes')
                                            ->dehydrated(true)
                                            ->afterStateHydrated(fn ($component) => $component->state(''))
                                            ->label('Revise Notes')
                                            ->helperText('Wajib diisi saat mengubah dari Finished ke Pending / On Progress.')
                                            ->debounce(300)
                                            ->live()
                                            ->reactive()
                                            ->required(fn () => $this->editedIndex === 9 && $this->pendingNewStatus !== null)
                                            ->hidden(fn () => $this->editedIndex !== 9)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if (filled($state)) {
                                                    $this->tryApplyPendingChange($get, $set);
                                                }
                                            })
                                    ])
                                    ->dehydrated(true),
                            ])
                            ->reactive()
                    ])
            ]);
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
        if (!isset($phases[$index])) {
            return $phases;
        }

        $this->ensureReviseNotesArray($phases[$index]);

        $user = auth()->user();
        $phases[$index]['reviseNotes'][] = [
            'tanggal' => now()->format(DATE_ATOM),
            'alasan'  => $reason,
            'user'    => $user?->name ?? $user?->email ?? (string) $user?->id ?? 'system',
        ];

        return $phases;
    }

    /** BUGFIX: jangan reset sebelum dipakai */
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

// ==== DIFF REAKTIF (untuk detail_change) ====
        $after = $before;
        $after['phases'] = $this->sanitizePhases($phases);
        $diff = $this->diffFormData($before, $after);
        $this->accumulatedChanges = array_merge($this->accumulatedChanges, $diff);

// ==== SET STATE FORM ====
        $phases = $this->sanitizePhases($phases);
        foreach ($phases as $i => $p) {
            if (array_key_exists('status', $p)) {
                $set("form_data.phases.$i.status", $p['status']);
            }
        }

// ==== SIMPAN ====
        $saved = $this->savePhases($phases);

// ==== TENTUKAN APAKAH STATUS BERUBAH (TANPA GANTUNG PADA $saved) ====
        $statusChanged = $this->phasesStatusChanged($dbPhases, $phases);
        $changed = ($dbPhases[$index]['status'] ?? null) !== $newStatus; // tetap catat perubahan di phase yg diubah

        $record->refresh();

        if ($statusChanged) {
            // Toast lokal
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

    protected function getRecipients($actor): Collection
    {
        return User::where(function ($query) {
            // Manager dari HR berdasarkan department ID
            $query->whereHas('roles', function ($q) {
                $q->whereIn('name', ['Manager', 'Team Leader']);
            })->where('department_id', $this->hrDepartmentId);
        })
            ->orWhere(function ($query) {
                // Team Leader dari department yang sama
                $query->whereHas('roles', function ($r) {
                    $r->where('name', 'Team Leader');
                })->where('department_id', $this->departmentId);
            })
            ->when($actor, function ($q) use ($actor) {
                $q->where('id', '!=', $actor->getKey());
            })
            ->get();
    }

    protected function getRecipientsEmail($actor): array
    {
        return User::where(function ($query) {
            // Manager dari HR berdasarkan department ID
            $query->whereHas('roles', function ($q) {
                $q->whereIn('name', ['Manager', 'Team Leader']);
            })->where('department_id', $this->hrDepartmentId);
        })
            ->orWhere(function ($query) {
                // Team Leader dari department yang sama
                $query->whereHas('roles', function ($r) {
                    $r->where('name', 'Team Leader');
                })->where('department_id', $this->departmentId);
            })
            ->when($actor, function ($q) use ($actor) {
                $q->where('id', '!=', $actor->getKey());
            })
            ->pluck('email')
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

    /**
     * Normalisasi value untuk perbandingan agar 1 vs "1" tidak dianggap beda.
     */
    protected function normalizeForDiff($v)
    {
        if (is_null($v)) return null;
        if (is_bool($v)) return $v ? '1' : '0';
        if (is_int($v) || is_float($v)) return (string) $v;
        if (is_string($v)) return $v;
        return json_encode($v);
    }

    /**
     * Buat daftar perubahan (diff) antara $before dan $after.
     * - Abaikan key pada $this->diffExcludeKeys
     * - Tangkap perubahan top-level form_data dan tiap phases[i].*
     */
    protected function diffFormData(array $before, array $after): array
    {
        $changes = [];

        // Top-level (selain phases)
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

        // Per-phase
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

        // Snapshot sebelum perubahan
        $before = $record->form_data ?? [];

        $db    = $record->form_data ?? [];
        $input = $data['form_data'] ?? [];

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

        if (isset($input['phases']) && is_array($input['phases'])) {
            $db['phases'] = $db['phases'] ?? [];

            foreach ($input['phases'] as $i => $phaseInput) {
                if (!isset($db['phases'][$i])) $db['phases'][$i] = [];

                if (is_array($phaseInput) && array_key_exists('form_data', $phaseInput)) {
                    unset($phaseInput['form_data']);
                }

                foreach ($phaseInput as $k => $v) {
                    if (in_array($k, ['status', 'reviseNotes'], true)) continue;
                    $db['phases'][$i][$k] = $v;
                }
            }
        }

        foreach ($input as $k => $v) {
            if ($k === 'phases') continue;
            $db[$k] = $v;
        }

        $db['phases'] = $this->sanitizePhases($db['phases'] ?? []);

        // === DIFF & SAVE ===
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

        // gabungkan reaktif + submit
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
