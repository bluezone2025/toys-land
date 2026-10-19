<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\User;
use App\Support\Analytics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '15s';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $sales = Analytics::sales();
        $revenueToday = (float) $sales->clone()->where('created_at', '>=', $today)->sum('total');
        $revenueMonth = (float) $sales->clone()->where('created_at', '>=', now()->startOfMonth())->sum('total');
        $ordersToday = $sales->clone()->where('created_at', '>=', $today)->count();
        $revenueTrend = array_values(Analytics::daily($sales->clone(), 'SUM(total)', 14));
        $visitorTrend = array_values(Analytics::daily(\App\Models\Visit::query(), 'COUNT(DISTINCT session_id)', 14));
        $visitorsToday = Analytics::uniqueVisitors($today);
        $orders30 = $sales->clone()->where('created_at', '>=', now()->subDays(30))->count();
        $visitors30 = max(1, Analytics::uniqueVisitors(now()->subDays(30)));
        $pending = Order::where('status', 'pending')->where(fn ($q) => $q->where('payment_method', 'cod')->orWhere('payment_status', 'paid'))->count();

        return [
            Stat::make('🟢 الزوار النشطين الآن', Analytics::activeVisitors())
                ->description('خلال آخر '.Analytics::ACTIVE_MINUTES.' دقائق')
                ->color('success'),
            Stat::make('مبيعات اليوم', number_format($revenueToday, 3).' د.ك')
                ->description($ordersToday.' طلب اليوم')
                ->chart($revenueTrend)->color('primary'),
            Stat::make('مبيعات الشهر', number_format($revenueMonth, 3).' د.ك')
                ->description('منذ بداية '.now()->translatedFormat('F')),
            Stat::make('طلبات بانتظار التحضير', $pending)
                ->description('جديدة ولم تبدأ')
                ->color($pending ? 'warning' : 'gray')
                ->url(route('filament.admin.resources.orders.index', ['tab' => 'pending'])),
            Stat::make('زوار اليوم', $visitorsToday)
                ->description('زائر فريد')
                ->chart($visitorTrend)->color('info'),
            Stat::make('معدل التحويل (30 يوم)', number_format($orders30 / $visitors30 * 100, 2).'%')
                ->description($orders30.' طلب من '.$visitors30.' زائر'),
            Stat::make('متوسط قيمة الطلب', number_format($orders30 ? (float) $sales->clone()->where('created_at', '>=', now()->subDays(30))->avg('total') : 0, 3).' د.ك')
                ->description('آخر 30 يوم'),
            Stat::make('العملاء المسجلين', User::where('is_admin', false)->count())
                ->description('+'.User::where('is_admin', false)->where('created_at', '>=', now()->subDays(7))->count().' هذا الأسبوع'),
        ];
    }
}
