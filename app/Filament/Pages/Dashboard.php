<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\OrdersStatusChart;
use App\Filament\Widgets\SalesChart;
use App\Filament\Widgets\StoreStatsOverview;
use App\Filament\Widgets\TopProducts;
use App\Filament\Widgets\VisitorsChart;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'لوحة التحكم';

    public function getWidgets(): array
    {
        return [
            StoreStatsOverview::class,
            SalesChart::class,
            OrdersStatusChart::class,
            VisitorsChart::class,
            LatestOrders::class,
            TopProducts::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 3;
    }
}
