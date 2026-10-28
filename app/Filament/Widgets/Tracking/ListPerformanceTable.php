<?php

namespace App\Filament\Widgets\Tracking;

use App\Support\TrackingReports;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

class ListPerformanceTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = '🗂️ أداء السلايدرات والقوائم';

    public function table(Table $table): Table
    {
        return $table
            ->records(function () {
                [$from, $to] = TrackingReports::range($this->pageFilters['period'] ?? '30');

                return collect(TrackingReports::lists($from, $to))->keyBy(fn ($r, $i) => 'l'.$i)->all();
            })
            ->paginated([10, 25])
            ->emptyStateHeading('ما في بيانات بالفترة هذي')
            ->columns([
                TextColumn::make('list')->label('القائمة')->wrap(),
                TextColumn::make('impressions')->label('ظهور'),
                TextColumn::make('clicks')->label('نقرات'),
                TextColumn::make('ctr')->label('نسبة النقر')->suffix('%')->placeholder('—'),
            ]);
    }
}
