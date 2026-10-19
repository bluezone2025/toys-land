<?php

namespace App\Filament\Widgets;

use App\Support\Analytics;
use Filament\Widgets\ChartWidget;

class SalesChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'المبيعات والطلبات (آخر 30 يوم)';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 2;

    protected function getData(): array
    {
        $revenue = Analytics::daily(Analytics::sales(), 'SUM(total)', 30);
        $orders = Analytics::daily(Analytics::sales(), 'COUNT(*)', 30);

        return [
            'datasets' => [
                ['label' => 'المبيعات (د.ك)', 'data' => array_values($revenue), 'borderColor' => '#0ea5e9', 'backgroundColor' => 'rgba(14,165,233,0.15)', 'fill' => true, 'tension' => 0.3, 'yAxisID' => 'y'],
                ['label' => 'عدد الطلبات', 'data' => array_values($orders), 'borderColor' => '#ec4899', 'backgroundColor' => '#ec4899', 'tension' => 0.3, 'yAxisID' => 'y1'],
            ],
            'labels' => array_map(fn ($d) => date('m/d', strtotime($d)), array_keys($revenue)),
        ];
    }

    protected function getOptions(): array
    {
        return ['scales' => ['y' => ['position' => 'left', 'beginAtZero' => true], 'y1' => ['position' => 'right', 'beginAtZero' => true, 'grid' => ['drawOnChartArea' => false], 'ticks' => ['precision' => 0]]]];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
