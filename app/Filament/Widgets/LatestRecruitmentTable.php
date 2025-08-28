<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\RecruitmentRequestResource;
use App\Models\RecruitmentRequest;
use App\Support\Filament\Concerns\ScopesRecruitmentRequests;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestRecruitmentTable extends BaseWidget
{
    use ScopesRecruitmentRequests;
   protected static ?string $heading = 'Latest Recruitment Requests';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->limit(40)
                    ->tooltip(fn($record) => $record->title)
                    ->searchable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable(),
                // v3: pakai TextColumn + ->badge() + ->color()
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state) => match (strtolower($state)) {
                        'pending' => 'danger',
                        'progress' => 'success',
                        'finish' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
}

    protected function getTableQuery(): Builder
    {
        return self::scopedRequests()
            ->with('department')
            ->latest()
            ->limit(10);
    }
}
