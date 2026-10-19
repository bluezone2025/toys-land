<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\Visit;
use App\Support\Analytics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TrafficStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '10s';

    protected ?string $heading = 'حركة الزوار ومسار الشراء (آخر 30 يوم)';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $from = now()->subDays(30);
        $sessions = max(1, Analytics::uniqueVisitors($from));
        $views = Visit::where('created_at', '>=', $from)->count();
        $events = AnalyticsEvent::where('created_at', '>=', $from)
            ->selectRaw('type, COUNT(DISTINCT session_id) as c')->groupBy('type')->pluck('c', 'type');
        $pct = fn ($n) => number_format(($n / $sessions) * 100, 1).'% من الزوار';

        return [
            Stat::make('🟢 نشطين الآن', Analytics::activeVisitors())->description('آخر '.Analytics::ACTIVE_MINUTES.' دقائق')->color('success'),
            Stat::make('زوار اليوم', Analytics::uniqueVisitors(now()->startOfDay())),
            Stat::make('زوار آخر 7 أيام', Analytics::uniqueVisitors(now()->subDays(7))),
            Stat::make('صفحات لكل زيارة', number_format($views / $sessions, 1))->description($views.' مشاهدة / '.$sessions.' زائر'),
            Stat::make('1. زاروا المتجر', $sessions)->description('زائر فريد'),
            Stat::make('2. أضافوا للسلة', (int) ($events['add_to_cart'] ?? 0))->description($pct($events['add_to_cart'] ?? 0))->color('info'),
            Stat::make('3. بدأوا الدفع', (int) ($events['begin_checkout'] ?? 0))->description($pct($events['begin_checkout'] ?? 0))->color('warning'),
            Stat::make('4. أتموا الشراء', (int) ($events['purchase'] ?? 0))->description($pct($events['purchase'] ?? 0))->color('success'),
        ];
    }
}
