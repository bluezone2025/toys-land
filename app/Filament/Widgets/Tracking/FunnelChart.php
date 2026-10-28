<?php

namespace App\Filament\Widgets\Tracking;

use App\Support\TrackingReports;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class FunnelChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'أين يتسرب الزوار؟';

    protected ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        [$from, $to] = TrackingReports::range($this->pageFilters['period'] ?? '30');
        $funnel = TrackingReports::funnel($from, $to);

        return [
            'datasets' => [[
                'label' => 'زوار فريدين',
                'data' => array_values($funnel),
                'backgroundColor' => ['#94a3b8', '#38bdf8', '#0ea5e9', '#f59e0b', '#f97316', '#ec4899', '#10b981'],
                'borderRadius' => 8,
            ]],
            'labels' => array_values(TrackingReports::FUNNEL),
        ];
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['display' => false]], 'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]]];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
