<?php

namespace App\Filament\Widgets;

use App\Support\Filament\Concerns\ScopesRecruitmentRequests;
use Filament\Widgets\ChartWidget;

class StatusDistributionChart extends ChartWidget
{
    use ScopesRecruitmentRequests;

    protected static ?string $heading = 'Requests by Status';

    protected static ?string $pollingInterval = '30s'; // optional

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $base = self::scopedRequests();

        $map = (clone $base)
            ->selectRaw('LOWER(status) as s, COUNT(*) as total')
            ->groupBy('s')
            ->pluck('total', 's');

        $labels = ['Pending', 'Progress', 'Finish'];
        $data = [
            (int) ($map['pending']  ?? 0),
            (int) ($map['progress'] ?? 0),
            (int) ($map['finish']   ?? 0),
        ];

        return [
            'datasets' => [[
                'label' => 'Requests',
                'data' => $data,
                'backgroundColor' => ['#ef4444', '#22c55e', '#f59e0b'], // merah, hijau, oren
                'borderRadius' => 8, // rounded bars (v3 ok)
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
