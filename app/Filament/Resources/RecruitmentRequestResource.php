<?php

namespace App\Filament\Resources;

use App\Models\RecruitmentRequest;
use App\Models\User;

use App\Support\AccessHelper;
use App\Support\Notify;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Tables;
use Filament\Tables\Table;

use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;

use Illuminate\Database\Eloquent\Model;

class RecruitmentRequestResource extends Resource
{
    protected static ?string $model = RecruitmentRequest::class;
    protected static ?string $navigationGroup = 'Request Management';
    protected static ?string $navigationIcon = 'fluentui-branch-request-20-o';
    protected static ?string $activeNavigationIcon = 'fluentui-branch-request-20';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return AccessHelper::canAccessGlobal();
    }
    public static function canView(Model $record): bool
    {
        return AccessHelper::canViewHR();
    }
    public static function canEdit(Model $record): bool
    {
        return AccessHelper::canEditHR();
    }
    public static function canDelete(Model $record): bool
    {
        return AccessHelper::canDeleteHR();
    }
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction('view_request') // ⬅️ klik baris = buka modal detail
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->alignment(Alignment::Center)
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('requester.name')->label('Diminta Oleh')->searchable(),
                Tables\Columns\TextColumn::make('department.name')->label('Department')->alignment(Alignment::Center)->searchable(),
                Tables\Columns\TextColumn::make('recruitment_type')->label('Jenis ')->formatStateUsing(fn($state) => (ucfirst($state)))->badge()->alignment(Alignment::Center),
                Tables\Columns\TextColumn::make('pic.name')->label('Penanggung Jawab')->badge()->alignment(Alignment::Center),
                Tables\Columns\TextColumn::make('recruitmentPhase')
                    ->label('Dalam Perkembangan')->badge()
                    ->getStateUsing(function ($record) {
                        $data = $record->recruitmentPhase['form_data']['phases'] ?? [];
                        $progressPhases = array_filter($data, fn ($item) => ($item['status'] ?? null) === 'progress');
                        return array_column($progressPhases, 'name');
                    })
                    ->alignment(Alignment::Center)
                    ->tooltip('Tekan untuk melihat detail'),
                Tables\Columns\IconColumn::make('status')
                    ->label('Status Approval')
                    ->alignment(Alignment::Center)
                    ->tooltip(fn ($record) => ucfirst($record->status ?? 'Pending'))
                    ->icon(fn ($record) => match ($record->status) {
                        'approved', 'finish'   => 'heroicon-o-check-circle',
                        'progress'             => 'heroicon-o-clock',
                        'rejected'             => 'heroicon-o-x-circle',
                        default                => 'heroicon-o-clock', // pending
                    })
                    ->color(fn ($record) => match ($record->status) {
                        'approved', 'finish'   => 'success',
                        'progress'             => 'warning',
                        'rejected'             => 'danger',
                        default                => 'gray', // pending
                    })
            ])
            ->actions([
                Tables\Actions\Action::make('view_request')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Detail Recruitment Request')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->infolist(function (Infolist $infolist, RecruitmentRequest $record) {
                        return $infolist
                            ->schema([
                                Section::make('Informasi Umum')
                                    ->collapsible()
                                    ->schema([
                                        Grid::make(12)->schema([
                                            TextEntry::make('title')->label('Judul')->columnSpan(12),
                                            TextEntry::make('status')
                                                ->label('Status')
                                                ->badge()
                                                ->color(fn (?string $state) => match ($state) {
                                                    'pending'   => 'warning',
                                                    'finish'  => 'success',
                                                    'rejected'  => 'danger',
                                                    default     => 'gray',
                                                })
                                                ->columnSpan(6),
                                            TextEntry::make('department.name')->label('Department')->columnSpan(6)->placeholder('-'),
                                            TextEntry::make('requester.name')->label('Diminta Oleh')->columnSpan(6)->placeholder('-'),
                                            TextEntry::make('pic.name')->label('PIC')->placeholder('-')->columnSpan(6),
                                            TextEntry::make('created_at')->dateTime()->label('Dibuat')->columnSpan(6),
                                            TextEntry::make('updated_at')->dateTime()->label('Diperbarui')->columnSpan(6),
                                        ]),
                                    ]),

                                Section::make('Approval')
                                    ->collapsible()
                                    ->schema([
                                        Grid::make(12)->schema([
                                            IconEntry::make('approval.status')
                                                ->label('Status Approval')
                                                ->boolean()
                                                ->trueIcon('heroicon-o-check-circle')
                                                ->falseIcon('heroicon-o-clock')
                                                ->state(fn ($record) => ($record->approval->status ?? null) === 'approved')
                                                ->columnSpan(4),
                                            IconEntry::make('approval.hrd_approval')
                                                ->label('HR Manager')
                                                ->boolean()
                                                ->trueIcon('heroicon-o-check-circle')
                                                ->falseIcon('heroicon-o-x-circle')
                                                ->state(fn ($record) => $record->approval->hrd_approval ? true : false)
                                                ->columnSpan(4),
                                            IconEntry::make('approval.director_approval')
                                                ->label('Direksi')
                                                ->boolean()
                                                ->trueIcon('heroicon-o-check-circle')
                                                ->falseIcon('heroicon-o-x-circle')
                                                ->state(fn ($record) => $record->approval->director_approval ? true : false)
                                                ->columnSpan(4),
                                        ]),
                                    ]),

                                Section::make('Formulir Permintaan')
                                    ->description('Rangkuman data pada kolom form_data')
                                    ->collapsible()
                                    ->schema([
                                        Section::make('Recruitment')
                                            ->schema([
                                                Grid::make(12)->schema([
                                                    TextEntry::make('form_data.recruitmentSection.tipeRekrutmen')->label('Tipe Rekrutmen')->columnSpan(12)->placeholder('-'),
                                                    TextEntry::make('form_data.recruitmentSection.jabatan')->label('Jabatan')->columnSpan(6)->placeholder('-'),
                                                    TextEntry::make('form_data.recruitmentSection.jumlahKaryawan')->label('Jumlah')->columnSpan(6)->placeholder('-'),
                                                    TextEntry::make('form_data.recruitmentSection.departemen')->label('Departemen')->columnSpan(6)->placeholder('-'),
                                                    TextEntry::make('form_data.recruitmentSection.lokasiPenempatan')->label('Lokasi Penempatan')->columnSpan(6)->placeholder('-'),
                                                    TextEntry::make('form_data.recruitmentSection.deskripsiPekerjaan')
                                                        ->label('Deskripsi Pekerjaan')
                                                        ->limit(250)->tooltip(fn ($record)=> ($record->form_data['recruitmentSection']['deskripsiPekerjaan']))->columnSpan(12)->placeholder('-'),
                                                    TextEntry::make('form_data.recruitmentSection.deskripsiPekerjaanKhusus')
                                                        ->label('Deskripsi Khusus')
                                                        ->limit(250)->tooltip(fn ($record)=> ($record->form_data['recruitmentSection']['deskripsiPekerjaanKhusus']))->columnSpan(12)->placeholder('-'),
                                                ]),
                                            ])
                                            ->collapsed(),

                                        Section::make('Kualifikasi')
                                            ->schema([
                                                Grid::make(12)->schema([
                                                    TextEntry::make('form_data.kualifikasi.pendidikan')->label('Pendidikan')->columnSpan(4)->placeholder('-'),
                                                    TextEntry::make('form_data.kualifikasi.pengalaman')->label('Pengalaman')->columnSpan(4)->placeholder('-'),
                                                    TextEntry::make('form_data.kualifikasi.jenisKelamin')->label('Jenis Kelamin')->columnSpan(4)->placeholder('-'),
                                                    TextEntry::make('form_data.kualifikasi.agama')->label('Agama')->columnSpan(4)->placeholder('-'),
                                                    TextEntry::make('form_data.kualifikasi.status')->label('Status')->columnSpan(4)->placeholder('-'),
                                                    TextEntry::make('form_data.kualifikasi.nilaiPlus')->label('Nilai Plus')->limit(200)->tooltip(fn ($record)=> ($record->form_data['kualifikasi']['nilaiPlus']))->columnSpan(12)->placeholder('-'),
                                                    TextEntry::make('form_data.kualifikasi.kemampuanLainnya')->label('Kemampuan Lain')->limit(200)->tooltip(fn ($record)=> ($record->form_data['kualifikasi']['kemampuanLainnya']))->columnSpan(12)->placeholder('-'),
                                                ]),
                                            ])
                                            ->collapsed(),

                                        Section::make('Kompensasi')
                                            ->schema([
                                                Grid::make(12)->schema([
                                                    TextEntry::make('form_data.kompensasi.gaji')
                                                        ->label('Gaji')
                                                        ->columnSpan(3)
                                                        ->placeholder('-')
                                                        ->formatStateUsing(fn ($state) => $state !== null ? 'Rp' . number_format($state, 0, ',', '.') : '-'),

                                                    TextEntry::make('form_data.kompensasi.tunjanganMakan')
                                                        ->label('Tunjangan Makan')
                                                        ->columnSpan(3)
                                                        ->placeholder('-')
                                                        ->formatStateUsing(fn ($state) => $state !== null ? 'Rp' . number_format($state, 0, ',', '.') : '-'),

                                                    TextEntry::make('form_data.kompensasi.tunjanganPerumahan')
                                                        ->label('Tunjangan Perumahan')
                                                        ->columnSpan(3)
                                                        ->placeholder('-')
                                                        ->formatStateUsing(fn ($state) => $state !== null ? 'Rp' . number_format($state, 0, ',', '.') : '-'),

                                                    TextEntry::make('form_data.kompensasi.tunjanganTransport')
                                                        ->label('Tunjangan Transport')
                                                        ->columnSpan(3)
                                                        ->placeholder('-')
                                                        ->formatStateUsing(fn ($state) => $state !== null ? 'Rp' . number_format($state, 0, ',', '.') : '-'),

                                                    TextEntry::make('form_data.kompensasi.tunjanganKomunikasi')
                                                        ->label('Tunjangan Komunikasi')
                                                        ->columnSpan(3)
                                                        ->placeholder('-')
                                                        ->formatStateUsing(fn ($state) => $state !== null ? 'Rp' . number_format($state, 0, ',', '.') : '-'),

                                                    TextEntry::make('form_data.kompensasi.hariKerja')
                                                        ->label('Hari Kerja')
                                                        ->columnSpan(9)
                                                        ->placeholder('-'),
                                                ]),
                                            ])
                                            ->collapsed()

                                    ]),
                            ]);
                    })
                    // Tambah tombol cepat di footer modal (opsional):
                    ->extraModalFooterActions([
                        Tables\Actions\Action::make('assign_pic_quick')
                            ->label('Assign PIC')
                            ->icon('heroicon-o-user-plus')
                            ->form([
                                Forms\Components\Select::make('pic_id')
                                    ->label('Pilih Staff HR')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->options(fn () => User::role('Staff')
                                        ->whereHas('departments', fn ($q) => $q->where('name', 'HRD'))
                                        ->orderBy('name')->pluck('name', 'id')->toArray()
                                    ),
                            ])
                            ->hidden(function ($record): bool {
                                if($record->pic_id){
                                    return true;
                                }
                                if ($record->approval?->hrd_approval) {
                                    return false;
                                }
                                return !AccessHelper::canAssignPIC();
                            })
                            ->action(function (array $data, RecruitmentRequest $record, Tables\Actions\Action $action) {
                                $oldPicId = $record->pic_id;
                                $record->forceFill(['pic_id' => $data['pic_id']])->save();
                                $record->load(['pic', 'department']);

                                $record->refresh();

                                $action->getLivewire()->dispatch('refresh');
                                $actor = auth()->user();

                                Notification::make()
                                    ->title('PIC berhasil ditetapkan')
                                    ->success()
                                    ->send();

                                if ($oldPicId !== $record->pic_id && $record->pic) {
                                    Notify::assignPICActivity(
                                        recipients: [$record->pic_id],
                                        recruitmentId: (string) $record->getKey(),
                                        action: 'assignTo',
                                        context: [
                                            'title' => $record->title,
                                        ],
                                        actorId: (string) ($actor->id ?? 'system'),
                                        actorName: $actor->name ?? 'System',
                                        department: $record->department->name ?? null,
                                    );
                                }
                            }),

                        Tables\Actions\Action::make('view_phases_quick')
                            ->label('Lihat Phases')
                            ->icon('heroicon-o-eye')
                            ->modal()
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->modalHeading('Perkembangan Phases')
                            ->modalContent(fn($record) => view('livewire.wizard-modal', [
                                'record' => $record->recruitmentPhase
                            ])),
                    ]),

                // (opsional) tetap sediakan tombol Assign PIC di baris
                Tables\Actions\Action::make('assign_pic')
                    ->label('Assign PIC')
                    ->icon('heroicon-o-user-plus')
                    ->hidden(function ($record): bool {
                        if($record->pic_id){
                            return true;
                        }
                        if ($record->approval?->hrd_approval) {
                            return false;
                        }
                        return !AccessHelper::canAssignPIC();
                    })
                    ->form([
                        Forms\Components\Select::make('pic_id')
                            ->label('Pilih Staff HR')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(fn () => User::role('Staff')
                                ->whereHas('departments', fn ($q) => $q->where('name', 'HRD'))
                                ->orderBy('name')->pluck('name', 'id')->toArray()
                            ),
                    ])
                    ->action(fn (array $data, RecruitmentRequest $record) => $record->update(['pic_id' => $data['pic_id']])),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->with(['approval', 'department', 'requester', 'pic', 'recruitmentPhase']);

        if (auth()->user()->isStaff()) {
            $query->where('pic_id', auth()->id())
                ->whereHas('approval', function ($query) {
                    $query->where('status', 'approved');
                });
        };
        return $query;
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\RecruitmentRequestResource\Pages\ListRecruitmentRequests::route('/'),
            'create' => \App\Filament\Resources\RecruitmentRequestResource\Pages\CreateRecruitmentRequest::route('/create'),
            'edit' => \App\Filament\Resources\RecruitmentRequestResource\Pages\EditRecruitmentRequest::route('/{record}/edit'),
        ];
    }
}
