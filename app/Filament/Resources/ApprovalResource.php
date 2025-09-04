<?php

namespace App\Filament\Resources;

use App\Filament\Admin\Resources\ApprovalResource\Pages;
use App\Filament\Admin\Resources\ApprovalResource\RelationManagers;
use App\Models\Approval;
use App\Support\AccessHelper;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Filament\Forms;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class ApprovalResource extends Resource
{
    protected static ?string $model = Approval::class;
    protected static ?string $navigationIcon = "heroicon-o-check-circle";
    protected static ?string $activeNavigationIcon = "heroicon-s-check-circle";
    protected static ?string $navigationGroup = 'Request Management';

    public static function canAccess(): bool
    {
        return AccessHelper::canAccessOnlyStakeHolder();
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        return AccessHelper::canViewGlobal();
    }

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('request.title')
                    ->label('Request Title')
                    ->alignment(Alignment::Center)
                    ->searchable(),
                Tables\Columns\TextColumn::make('request.department.name')
                    ->searchable(),
                Tables\Columns\IconColumn::make('hrd_approval')
                    ->label('Approval By HRD')
                    ->alignment(Alignment::Center)
                    ->tooltip(fn($record): string => $record['hrd_decided_at'] ? (date_format($record['hrd_decided_at'], 'd F Y h:i A')) : '')
                    ->boolean(),
                Tables\Columns\IconColumn::make('director_approval')
                    ->label('Approval By Direction')
                    ->alignment(Alignment::Center)
                    ->tooltip(fn($record): string => $record['director_decided_at'] ? (date_format($record['director_decided_at'], 'd F Y h:i A')) : '')
                    ->boolean(),
                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->since()
                    ->label('Approved At')
                    ->tooltip(fn($record): string => $record['approved_at'] ? (date_format($record['approved_at'], 'd F Y h:i A')) : '')
                    ->alignment(Alignment::Center)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('directorDecision')
                    ->label('Keputusan Direktur')
                    ->icon('heroicon-o-check-circle')
                    ->visible(function (Approval $record) {
                        if (auth()->user()->isSU()){
                            return true;
                        };
                        return is_null($record->director_approval)
                        && auth()->check()
                        && auth()->user()->isDirector()
                        && auth()->user()->departments()->where('departments.id', $record->request->department->id)->exists();
                    })
                    ->modalHeading('Tentukan Keputusan Direktur')
                    ->modalSubmitActionLabel('Simpan')
                    ->form([
                        Forms\Components\Select::make('decision')
                            ->label('Keputusan')
                            ->options([
                                'approve' => 'Setujui',
                                'reject'  => 'Tolak',
                            ])->required(),
                        Forms\Components\Textarea::make('note')->label('Catatan (opsional)')->maxLength(500),
                    ])
                    ->action(function (array $data, Approval $record) {
                        DB::transaction(function () use ($data, $record) {
                            $record->director_approval   = $data['decision'] === 'approve';
                            $record->director_decided_at = now();
                            $record->save();

                            // Hanya proses final kalau HRD sudah memutuskan
                            if (! is_null($record->hrd_approval)) {
                                $phase = $record->request->recruitmentPhase;
                                $formData = (array) ($phase->form_data ?? []);
                                $formData['phases'] = $formData['phases'] ?? [];
                                $formData['phases'][1] = is_array($formData['phases'][1] ?? null) ? $formData['phases'][1] : [];
                                $formData['phases'][2] = is_array($formData['phases'][2] ?? null) ? $formData['phases'][2] : [];

                                if ($record->hrd_approval === false) {
                                    // HR menolak → final REJECT
                                    $record->status = 'rejected';
                                    $record->approved_at = now();

                                    $record->request->status = 'rejected';
                                    $record->request->save();

                                    $formData['phases'][1]['status']    = 'cancel';
                                    $formData['phases'][1]['updatedAt'] = now()->toIso8601String();
                                    if ($phase) {
                                        $phase->update(['form_data' => $formData]);
                                    }
                                } else {
                                    // HR setuju → ikuti keputusan direktur
                                    if ($record->director_approval === true) {
                                        // Final APPROVE
                                        $record->status = 'approved';
                                        $record->approved_at = now();

                                        $record->request->status = 'progress';
                                        $record->request->save();

                                        $formData['phases'][1]['status']    = 'finish';
                                        $formData['phases'][1]['updatedAt'] = now()->toIso8601String();
                                        $formData['phases'][2]['status']    = 'progress';
                                        $formData['phases'][2]['updatedAt'] = now()->toIso8601String();
                                        if ($phase) {
                                            $phase->update(['form_data' => $formData]);
                                        }
                                    } else {
                                        // Direktur tolak → final REJECT
                                        $record->status = 'rejected';
                                        $record->approved_at = now();

                                        $record->request->status = 'rejected';
                                        $record->request->save();

                                        $formData['phases'][1]['status']    = 'cancel';
                                        $formData['phases'][1]['updatedAt'] = now()->toIso8601String();
                                        if ($phase) {
                                            $phase->update(['form_data' => $formData]);
                                        }
                                    }
                                }

                                $record->save();
                            }
                        });

                        Notification::make()
                            ->success()
                            ->title('Keputusan tersimpan')
                            ->body('Keputusan direktur berhasil disimpan dan status diperbarui.')
                            ->send();
                    }),

                Action::make('managerDecision')
                    ->label('Keputusan Manager HR')
                    ->icon('heroicon-o-check-circle')
                    ->visible(function (Approval $record) {
                        if (auth()->user()->isSU()){
                            return true;
                        };
                        return is_null($record->director_approval)
                            && auth()->check()
                            && auth()->user()->isDirector()
                            && auth()->user()->departments()->where('departments.id', $record->request->department->id)->exists();
                    })
                    ->modalHeading('Tentukan Keputusan Manager')
                    ->modalSubmitActionLabel('Simpan')
                    ->form([
                        Forms\Components\Select::make('decision')
                            ->label('Keputusan')
                            ->options([
                                'approve' => 'Setujui',
                                'reject'  => 'Tolak',
                            ])->required(),
                        Forms\Components\Textarea::make('note')->label('Catatan (opsional)')->maxLength(500),
                    ])
                    ->action(function (array $data, Approval $record) {
                        DB::transaction(function () use ($data, $record) {
                            // ⛔ FIX: Manager HR harus set hrd_approval
                            $record->hrd_approval   = $data['decision'] === 'approve';
                            $record->hrd_decided_at = now();
                            $record->save();

                            // Hanya proses final kalau Direktur sudah memutuskan
                            if (! is_null($record->director_approval)) {
                                $phase = $record->request->recruitmentPhase;
                                $formData = (array) ($phase->form_data ?? []);
                                $formData['phases'] = $formData['phases'] ?? [];
                                $formData['phases'][1] = is_array($formData['phases'][1] ?? null) ? $formData['phases'][1] : [];
                                $formData['phases'][2] = is_array($formData['phases'][2] ?? null) ? $formData['phases'][2] : [];

                                if ($record->director_approval === false) {
                                    // Direktur tolak → final REJECT
                                    $record->status = 'rejected';
                                    $record->approved_at = now();

                                    $record->request->status = 'rejected';
                                    $record->request->save();

                                    $formData['phases'][1]['status']    = 'cancel';
                                    $formData['phases'][1]['updatedAt'] = now()->toIso8601String();
                                    if ($phase) {
                                        $phase->update(['form_data' => $formData]);
                                    }
                                } else {
                                    // Direktur setuju → ikuti keputusan HR
                                    if ($record->hrd_approval === true) {
                                        // Final APPROVE
                                        $record->status = 'approved';
                                        $record->approved_at = now();

                                        $record->request->status = 'progress';
                                        $record->request->save();

                                        $formData['phases'][1]['status']    = 'finish';
                                        $formData['phases'][1]['updatedAt'] = now()->toIso8601String();
                                        $formData['phases'][2]['status']    = 'progress';
                                        $formData['phases'][2]['updatedAt'] = now()->toIso8601String();
                                        if ($phase) {
                                            $phase->update(['form_data' => $formData]);
                                        }
                                    } else {
                                        // HR menolak → final REJECT
                                        $record->status = 'rejected';
                                        $record->approved_at = now();

                                        $record->request->status = 'rejected';
                                        $record->request->save();

                                        $formData['phases'][1]['status']    = 'cancel';
                                        $formData['phases'][1]['updatedAt'] = now()->toIso8601String();
                                        if ($phase) {
                                            $phase->update(['form_data' => $formData]);
                                        }
                                    }
                                }

                                $record->save();
                            }
                        });

                        Notification::make()
                            ->success()
                            ->title('Keputusan tersimpan')
                            ->body('Keputusan manager HR berhasil disimpan dan status diperbarui.')
                            ->send();
                    }),
            ])

            ->filters([
                //
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'request'
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\ApprovalResource\Pages\ListApprovals::route('/'),
            'create' => \App\Filament\Resources\ApprovalResource\Pages\CreateApproval::route('/create'),
            'edit' => \App\Filament\Resources\ApprovalResource\Pages\EditApproval::route('/{record}/edit'),
        ];
    }
}
