<?php

namespace App\Filament\Widgets\Tracking;

use App\Models\AbandonedCheckout;
use App\Support\Analytics;
use App\Support\TrackingReports;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LiveStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '5s';

    protected ?string $heading = 'اللحظة الحالية';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $today = Analytics::sales()->where('created_at', '>=', now()->startOfDay());
        $open = AbandonedCheckout::where('status', 'open')->where('created_at', '>=', now()->startOfDay());

        return [
            Stat::make('🟢 متواجدين الآن', Analytics::activeVisitors())->description('آخر '.Analytics::ACTIVE_MINUTES.' دقائق')->color('success'),
            Stat::make('🛒 أضافوا للسلة', TrackingReports::liveCount('add_to_cart', 30))->description('آخر 30 دقيقة')->color('info'),
            Stat::make('💳 في صفحة الدفع', TrackingReports::liveCount('begin_checkout', 30))->description('آخر 30 دقيقة')->color('warning'),
            Stat::make('🎉 طلبات اليوم', $today->clone()->count())->description(TrackingReports::money($today->clone()->sum('total')))->color('success'),
            Stat::make('⚠️ دفعات متروكة اليوم', $open->clone()->count())->description('قيمتها '.TrackingReports::money($open->clone()->sum('subtotal')))->color('danger')
                ->url(\App\Filament\Resources\AbandonedCheckouts\AbandonedCheckoutResource::getUrl()),
            Stat::make('👥 زوار اليوم', Analytics::uniqueVisitors(now()->startOfDay())),
        ];
    }
}
