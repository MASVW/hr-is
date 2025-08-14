<?php

namespace App\Filament\Resources\RecruitmentPhaseResource\Pages;

use App\Filament\Resources\RecruitmentPhaseResource;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;


class EditRecruitmentPhase extends EditRecord
{
    protected static string $resource = RecruitmentPhaseResource::class;
    public ?int $pendingIndex = null;
    public ?string $pendingNewStatus = null;
    public ?int $editedIndex = 0;
    public function form(Form $form): Form
    {
        $statusOption = [
            "finish" => "Finished",
            "progress" => "On Progress",
        ];
        return $form
            ->schema([
                TextInput::make('status')
                    ->disabled()
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($component) {
                        $component->state(fn($state) => ucfirst($state));
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
                                    ->badge(fn($record) => ucfirst($record->form_data['phases'][2]['status'] ?? ''))
                                    ->badgeColor(fn($record) => match ($record->form_data['phases'][2]['status'] ?? null) {
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
                                            ->afterStateHydrated(fn($component) => $component->state(''))
                                            ->label('Revise Notes')
                                            ->helperText('Wajib diisi saat mengubah dari Finished ke Pending / On Progress.')
                                            ->debounce(300)
                                            ->live()
                                            ->reactive()
                                            ->required(fn() => $this->editedIndex === 2 && $this->pendingNewStatus !== null)
                                            ->hidden(fn() => $this->editedIndex !== 2)
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
                                    ->badge(fn($record) => ucfirst($record->form_data['phases'][3]['status'] ?? ''))
                                    ->badgeColor(fn($record) => match ($record->form_data['phases'][3]['status'] ?? null) {
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
                                            ->afterStateHydrated(fn($component) => $component->state(''))
                                            ->label('Revise Notes')
                                            ->helperText('Wajib diisi saat mengubah dari Finished ke Pending / On Progress.')
                                            ->debounce(300)
                                            ->live()
                                            ->reactive()
                                            ->required(fn() => $this->editedIndex === 3 && $this->pendingNewStatus !== null)
                                            ->hidden(fn() => $this->editedIndex !== 3)
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
                                    ->badge(fn($record) => ucfirst($record->form_data['phases'][4]['status'] ?? ''))
                                    ->badgeColor(fn($record) => match ($record->form_data['phases'][4]['status'] ?? null) {
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
                                                $this->editedIndex = 4;
                                                $this->onPhaseStatusChange(4, $state, $get, $set);
                                            }),
                                        TextInput::make('candidate')
                                            ->numeric(),
                                        TextInput::make('checked')
                                            ->numeric(),
                                        TextInput::make('passed')
                                            ->numeric(),
                                        Textarea::make('note'),
                                        Textarea::make('reviseNotes')
                                            ->dehydrated(true)
                                            ->afterStateHydrated(fn($component) => $component->state(''))
                                            ->label('Revise Notes')
                                            ->helperText('Wajib diisi saat mengubah dari Finished ke Pending / On Progress.')
                                            ->debounce(300)
                                            ->live()
                                            ->reactive()
                                            ->required(fn() => $this->editedIndex === 4 && $this->pendingNewStatus !== null)
                                            ->hidden(fn() => $this->editedIndex !== 4)
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
                                    ->badge(fn($record) => ucfirst($record->form_data['phases'][5]['status'] ?? ''))
                                    ->badgeColor(fn($record) => match ($record->form_data['phases'][5]['status'] ?? null) {
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
                                        TextInput::make('candidate')
                                            ->numeric(),
                                        TextInput::make('finished')
                                            ->numeric(),
                                        TextInput::make('passed')
                                            ->numeric(),
                                        Textarea::make('note'),
                                        Textarea::make('reviseNotes')
                                            ->dehydrated(true)
                                            ->afterStateHydrated(fn($component) => $component->state(''))
                                            ->label('Revise Notes')
                                            ->helperText('Wajib diisi saat mengubah dari Finished ke Pending / On Progress.')
                                            ->debounce(300)
                                            ->live()
                                            ->reactive()
                                            ->required(fn() => $this->editedIndex === 5 && $this->pendingNewStatus !== null)
                                            ->hidden(fn() => $this->editedIndex !== 5)
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if (filled($state)) {
                                                    $this->tryApplyPendingChange($get, $set);
                                                }
                                            })
                                    ])->dehydrated(true),

                                Tabs\Tab::make('HRD Interview')
                                    ->statePath('phases.6')
                                    ->icon('heroicon-o-chat-bubble-left-right')
                                    ->badge(fn($record) => ucfirst($record->form_data['phases'][6]['status'] ?? ''))
                                    ->badgeColor(fn($record) => match ($record->form_data['phases'][6]['status'] ?? null) {
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
                                        TextInput::make('interviewed')
                                            ->numeric(),
                                        TextInput::make('candidate')
                                            ->numeric(),
                                        TextInput::make('passed')
                                            ->numeric(),
                                        Textarea::make('note'),
                                        Textarea::make('reviseNotes')
                                            ->dehydrated(true)
                                            ->afterStateHydrated(fn($component) => $component->state(''))
                                            ->label('Revise Notes')
                                            ->helperText('Wajib diisi saat mengubah dari Finished ke Pending / On Progress.')
                                            ->debounce(300)
                                            ->live()
                                            ->reactive()
                                            ->required(fn() => $this->editedIndex === 6 && $this->pendingNewStatus !== null)
                                            ->hidden(fn() => $this->editedIndex !== 6)
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
                                    ->badge(fn($record) => ucfirst($record->form_data['phases'][7]['status'] ?? ''))
                                    ->badgeColor(fn($record) => match ($record->form_data['phases'][7]['status'] ?? null) {
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
                                        TextInput::make('interviewed')
                                            ->numeric(),
                                        TextInput::make('candidate')
                                            ->numeric(),
                                        TextInput::make('passed')
                                            ->numeric(),
                                        Textarea::make('note'),
                                        Textarea::make('reviseNotes')
                                            ->dehydrated(true)
                                            ->afterStateHydrated(fn($component) => $component->state(''))
                                            ->label('Revise Notes')
                                            ->helperText('Wajib diisi saat mengubah dari Finished ke Pending / On Progress.')
                                            ->debounce(300)
                                            ->live()
                                            ->reactive()
                                            ->required(fn() => $this->editedIndex === 7 && $this->pendingNewStatus !== null)
                                            ->hidden(fn() => $this->editedIndex !== 7)
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
                                    ->badge(fn($record) => ucfirst($record->form_data['phases'][8]['status'] ?? ''))
                                    ->badgeColor(fn($record) => match ($record->form_data['phases'][8]['status'] ?? null) {
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
                                        TextInput::make('candidate')
                                            ->numeric(),
                                        TextInput::make('offered')
                                            ->numeric(),
                                        TextInput::make('agreed')
                                            ->numeric(),
                                        Textarea::make('note'),
                                        Textarea::make('reviseNotes')
                                            ->dehydrated(true)
                                            ->afterStateHydrated(fn($component) => $component->state(''))
                                            ->label('Revise Notes')
                                            ->helperText('Wajib diisi saat mengubah dari Finished ke Pending / On Progress.')
                                            ->debounce(300)
                                            ->live()
                                            ->reactive()
                                            ->required(fn() => $this->editedIndex === 8 && $this->pendingNewStatus !== null)
                                            ->hidden(fn() => $this->editedIndex !== 8)
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
                                    ->badge(fn($record) => ucfirst($record->form_data['phases'][9]['status'] ?? ''))
                                    ->badgeColor(fn($record) => match ($record->form_data['phases'][9]['status'] ?? null) {
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
                                        TextInput::make('onboarded')
                                            ->numeric(),
                                        Textarea::make('note'),
                                        Textarea::make('reviseNotes')
                                            ->dehydrated(true)
                                            ->afterStateHydrated(fn($component) => $component->state(''))
                                            ->label('Revise Notes')
                                            ->helperText('Wajib diisi saat mengubah dari Finished ke Pending / On Progress.')
                                            ->debounce(300)
                                            ->live()
                                            ->reactive()
                                            ->required(fn() => $this->editedIndex === 9 && $this->pendingNewStatus !== null)
                                            ->hidden(fn() => $this->editedIndex !== 9)
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
            'tanggal' => now()->format(DATE_ATOM), // ISO-8601
            'alasan'  => $reason,
            'user'    => $user?->name ?? $user?->email ?? (string) $user?->id ?? 'system',
        ];

        return $phases;
    }

    protected function tryApplyPendingChange(Get $get, Set $set): void
    {
        if ($this->pendingIndex === null || $this->pendingNewStatus === null) return;

        $idx    = $this->pendingIndex;
        $reason = trim((string) ($get("form_data.phases.$idx.reviseNotes") ?? '')); // ← pakai 'reviseNotes'

        if ($reason === '') return;

        // bersihkan flag agar tidak loop
        $this->pendingIndex = null;
        $this->pendingNewStatus = null;

        // terapkan perubahan
        $this->onPhaseStatusChange($idx, $this->pendingNewStatus, $get, $set);
    }


    protected function onPhaseStatusChange(int $index, string $newStatus, Get $get, Set $set): void
    {
        $record   = $this->getRecord();
        $dbPhases = $record->form_data['phases'] ?? [];
        if (empty($dbPhases) || !isset($dbPhases[$index])) return;

        $oldStatus = $dbPhases[$index]['status'] ?? null;
        $requires  = $this->requiresReviseNotes($oldStatus, $newStatus);

        if ($requires) {
            $this->editedIndex = $index;

            $reason = trim((string) ($get("form_data.phases.$index.reviseNotes") ?? ''));
            if ($reason === '') {
                // tandai pending dan minta alasan
                $this->pendingIndex = $index;
                $this->pendingNewStatus = $newStatus;

                Notification::make()
                    ->title('Mohon isi alasan revisi')
                    ->body('Perubahan dari Finished ke Pending/On Progress wajib menyertakan "Revise Notes".')
                    ->warning()
                    ->send();

                return;
            }

            $dbPhases = $this->appendReviseLog($dbPhases, $index, $reason);
            // bersihkan input sementara di UI
            $set("form_data.phases.$index.reviseNotes", null);
        } else {
            // Tidak perlu revise
            $this->editedIndex = null;
            $this->pendingIndex = null;
            $this->pendingNewStatus = null;
        }

        // Terapkan aturan status + sinkron ke form
        $phases = $this->applyRules($dbPhases, $index, $newStatus);
        $phases = $this->sanitizePhases($phases);

        foreach ($phases as $i => $p) {
            if (array_key_exists('status', $p)) {
                $set("form_data.phases.$i.status", $p['status']);
            }
        }

        $this->savePhases($phases);
    }

    protected function sanitizePhases(array $phases): array
    {
        foreach ($phases as &$phase) {
            if (is_array($phase) && array_key_exists('form_data', $phase)) {
                unset($phase['form_data']); // buang kontaminan
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

        // === NEW: pending rule ===
        if ($newStatus === 'pending') {
            // Set semua jadi pending dulu
            for ($i = 0; $i < $total; $i++) {
                $phases[$i]['status'] = 'pending';
            }

            // Fase sebelum index jadi progress (atau 0 jika index=0)
            $prev = max(0, $index - 1);

            // Semua sebelum 'prev' = finish
            for ($i = 0; $i < $prev; $i++) {
                $phases[$i]['status'] = 'finish';
            }

            // 'prev' = progress, index..akhir tetap pending
            $phases[$prev]['status'] = 'progress';

            return $phases;
        }

        return $phases;
    }

    protected function savePhases(array $phases): void
    {
        $record = $this->getRecord();

        $data = $record->form_data ?? [];
        $data['phases'] = $this->sanitizePhases($phases);
        $record->form_data = $data;
        $record->save();

        // penting: segarkan record & isi ulang form
        $this->getRecord()->refresh();
        $this->fillForm();

        Notification::make()
            ->title('Phase updated')
            ->success()
            ->send();
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // pastikan $record terbaru (auto-save sebelumnya tidak ditimpa)
        $record->refresh();

        $db    = $record->form_data ?? [];
        $input = $data['form_data'] ?? [];

        // Fallback: kalau user tekan Submit dan ada perubahan tertunda
        if ($this->pendingIndex !== null && $this->pendingNewStatus !== null) {
            $idx    = $this->pendingIndex;
            $reason = trim((string) ($input['phases'][$idx]['reviseNotes'] ?? '')); // ← baca dari payload

            if ($reason !== '' && isset(($db['phases'] ?? [])[$idx])) {
                $db['phases'] = $this->appendReviseLog($db['phases'], $idx, $reason);
                $db['phases'] = $this->applyRules($db['phases'], $idx, $this->pendingNewStatus);

                // clear flag
                $this->pendingIndex = null;
                $this->pendingNewStatus = null;
                $this->editedIndex = null;
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
                    // ⬇️ JANGAN biarkan string 'reviseNotes' overwrite array di DB
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
        $record->form_data = $db;
        $record->save();

        return $record;
    }


    protected function afterSave(): void
    {
        $this->getRecord()->refresh();
        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
