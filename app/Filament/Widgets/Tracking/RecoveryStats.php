<?php

namespace App\Filament\Widgets\Tracking;

use App\Support\TrackingReports;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RecoveryStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = '🔁 استرجاع الدفعات المتروكة';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        [$from, $to] = TrackingReports::range($this->pageFilters['period'] ?? '30');
        $c = TrackingReports::checkouts($from, $to);
        $total = max(1, array_sum([$c['open'], $c['contacted'], $c['recovered'], $c['converted']]));

        return [
            Stat::make('متروكة ولم يتم التواصل', $c['open'])->description('قيمتها '.TrackingReports::money($c['open_value']))->color('danger'),
            Stat::make('تم التواصل وبانتظار الرد', $c['contacted'])->color('warning'),
            Stat::make('رجعوا وطلبوا بعد التواصل', $c['recovered'])->description('مبيعات مسترجعة '.TrackingReports::money($c['recovered_value']))->color('success'),
            Stat::make('كملوا الطلب بأنفسهم', $c['converted'])->description(number_format(($c['recovered'] + $c['converted']) / $total * 100, 1).'% من كل الدفعات اكتملت'),
        ];
    }
}
