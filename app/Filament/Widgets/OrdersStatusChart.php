<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrdersStatusChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'حالات الطلبات (آخر 30 يوم)';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $counts = Order::where('created_at', '>=', now()->subDays(30))->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        $colors = ['pending' => '#94a3b8', 'processing' => '#f59e0b', 'shipped' => '#0ea5e9', 'delivered' => '#10b981', 'cancelled' => '#ef4444'];

        return [
            'datasets' => [[
                'data' => array_map(fn ($s) => (int) ($counts[$s] ?? 0), array_keys(OrderResource::STATUS)),
                'backgroundColor' => array_values($colors),
            ]],
            'labels' => array_values(OrderResource::STATUS),
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
