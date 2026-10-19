<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Visit;
use Filament\Widgets\ChartWidget;

class HourlyTrafficChart extends ChartWidget
{
    protected ?string $heading = 'الزيارات حسب الساعة (آخر 7 أيام)';

    protected ?string $description = 'يساعدك تعرف أفضل وقت للإعلانات والعروض';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '240px';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $hours = array_fill(0, 24, 0);
        Visit::where('created_at', '>=', now()->subDays(7))->select('id', 'created_at')
            ->lazyById(2000)->each(function ($v) use (&$hours) {
                $hours[(int) $v->created_at->format('G')]++;
            });

        return [
            'datasets' => [['label' => 'مشاهدات', 'data' => $hours, 'backgroundColor' => '#f59e0b', 'borderRadius' => 4]],
            'labels' => array_map(fn ($h) => sprintf('%02d:00', $h), range(0, 23)),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
