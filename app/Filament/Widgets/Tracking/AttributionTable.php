<?php

namespace App\Filament\Widgets\Tracking;

use App\Support\TrackingReports;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

class AttributionTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = '📣 المبيعات حسب مصدر الزيارة والحملة (آخر نقرة قبل الطلب)';

    public function table(Table $table): Table
    {
        return $table
            ->records(function () {
                [$from, $to] = TrackingReports::range($this->pageFilters['period'] ?? '30');

                return collect(TrackingReports::attribution($from, $to))->keyBy(fn ($r, $i) => 'r'.$i)->all();
            })
            ->paginated([10, 25, 50])
            ->emptyStateHeading('ما في بيانات بالفترة هذي')
            ->columns([
                TextColumn::make('source')->label('المصدر')->weight('bold'),
                TextColumn::make('medium')->label('النوع')->badge()->color('gray'),
                TextColumn::make('campaign')->label('الحملة')->wrap(),
                TextColumn::make('visitors')->label('زوار'),
                TextColumn::make('orders')->label('طلبات'),
                TextColumn::make('conversion')->label('التحويل')->suffix('%')->placeholder('—'),
                TextColumn::make('revenue')->label('المبيعات')->formatStateUsing(fn ($state) => \App\Support\TrackingReports::money($state))->weight('bold')->color('success'),
                TextColumn::make('aov')->label('متوسط الطلب')->formatStateUsing(fn ($state) => \App\Support\TrackingReports::money($state)),
            ]);
    }
}
