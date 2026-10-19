<?php

namespace App\Filament\Widgets\Analytics;

use App\Support\Analytics;
use Filament\Widgets\ChartWidget;

class BrowsersChart extends ChartWidget
{
    protected ?string $heading = 'المتصفحات وأنظمة التشغيل (30 يوم)';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '240px';

    protected function getData(): array
    {
        $browsers = Analytics::countBy('browser');
        $platforms = Analytics::countBy('platform');

        return [
            'datasets' => [
                ['label' => 'المتصفح', 'data' => array_values($browsers + array_fill_keys(array_keys($platforms), null)), 'backgroundColor' => '#6366f1', 'borderRadius' => 6],
                ['label' => 'النظام', 'data' => array_values(array_fill_keys(array_keys($browsers), null) + $platforms), 'backgroundColor' => '#10b981', 'borderRadius' => 6],
            ],
            'labels' => [...array_keys($browsers), ...array_keys($platforms)],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
