<?php

namespace App\Filament\Widgets\Analytics;

use App\Support\Analytics;
use Filament\Widgets\ChartWidget;

class DevicesChart extends ChartWidget
{
    protected ?string $heading = 'الأجهزة (30 يوم)';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '240px';

    protected function getData(): array
    {
        $data = Analytics::countBy('device');
        $labels = ['mobile' => 'جوال', 'desktop' => 'كمبيوتر', 'tablet' => 'تابلت'];

        return [
            'datasets' => [['data' => array_values($data), 'backgroundColor' => ['#0ea5e9', '#ec4899', '#f59e0b', '#10b981']]],
            'labels' => array_map(fn ($k) => $labels[$k] ?? $k, array_keys($data)),
        ];
    }

    protected function getOptions(): array
    {
        return ['scales' => ['x' => ['display' => false], 'y' => ['display' => false]]];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
