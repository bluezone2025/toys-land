<?php

namespace App\Filament\Widgets\Tracking;

use App\Support\Analytics;
use App\Support\TrackingReports;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FunnelStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = '60s';

    protected ?string $heading = 'مسار الشراء: كم زائر وصل لكل مرحلة';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        [$from, $to] = TrackingReports::range($this->pageFilters['period'] ?? '30');
        $funnel = TrackingReports::funnel($from, $to);
        $visitors = max(1, $funnel['visitors']);
        $stats = [];
        $previous = null;
        $n = 1;
        foreach (TrackingReports::FUNNEL as $step => $label) {
            $count = $funnel[$step];
            $desc = $step === 'visitors' ? 'زائر فريد' : number_format($count / $visitors * 100, 1).'% من الزوار'
                .($previous ? ' · '.number_format($previous ? $count / $previous * 100 : 0, 0).'% من المرحلة السابقة' : '');
            $stats[] = Stat::make(($n++).'. '.$label, number_format($count))->description($desc)
                ->color($step === 'purchase' ? 'success' : ($step === 'visitors' ? 'gray' : 'info'));
            $previous = max(1, $count);
        }

        $sales = Analytics::sales()->whereBetween('created_at', [$from, $to]);
        $orders = $sales->clone()->count();
        $revenue = (float) $sales->clone()->sum('total');
        $stats[] = Stat::make('💰 المبيعات', TrackingReports::money($revenue))
            ->description($orders.' طلب · متوسط الطلب '.TrackingReports::money($orders ? $revenue / $orders : 0)
                .' · نسبة التحويل '.number_format($orders / $visitors * 100, 2).'%')
            ->color('success');

        return $stats;
    }
}
