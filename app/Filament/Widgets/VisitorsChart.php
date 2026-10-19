<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use App\Support\Analytics;
use Filament\Widgets\ChartWidget;

class VisitorsChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'الزوار ومشاهدات الصفحات (آخر 30 يوم)';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $visitors = Analytics::daily(Visit::query(), 'COUNT(DISTINCT session_id)', 30);
        $views = Analytics::daily(Visit::query(), 'COUNT(*)', 30);

        return [
            'datasets' => [
                ['label' => 'زوار فريدين', 'data' => array_values($visitors), 'backgroundColor' => '#6366f1', 'borderRadius' => 6],
                ['label' => 'مشاهدات الصفحات', 'data' => array_values($views), 'backgroundColor' => 'rgba(14,165,233,0.35)', 'borderRadius' => 6],
            ],
            'labels' => array_map(fn ($d) => date('m/d', strtotime($d)), array_keys($visitors)),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
