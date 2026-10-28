<?php

namespace App\Filament\Widgets\Tracking;

use App\Support\TrackingReports;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

class ProductPerformanceTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = '🧸 أداء المنتجات: مشاهدة ← سلة ← شراء';

    public function table(Table $table): Table
    {
        return $table
            ->records(function () {
                [$from, $to] = TrackingReports::range($this->pageFilters['period'] ?? '30');

                return collect(TrackingReports::products($from, $to, 50))->keyBy(fn ($r) => 'p'.$r['id'])->all();
            })
            ->paginated([10, 25, 50])
            ->emptyStateHeading('ما في بيانات بالفترة هذي')
            ->columns([
                TextColumn::make('name')->label('المنتج')->weight('bold')->wrap()
                    ->url(fn ($record) => $record['slug'] ? route('products.show', $record['slug']) : null, shouldOpenInNewTab: true),
                TextColumn::make('clicks')->label('نقرات'),
                TextColumn::make('views')->label('مشاهدات'),
                TextColumn::make('wishlist')->label('مفضلة'),
                TextColumn::make('carts')->label('للسلة'),
                TextColumn::make('cart_rate')->label('مشاهدة→سلة')->suffix('%')->placeholder('—')
                    ->color(fn ($state) => $state === null ? 'gray' : ($state >= 10 ? 'success' : ($state >= 4 ? 'warning' : 'danger'))),
                TextColumn::make('orders')->label('طلبات'),
                TextColumn::make('buy_rate')->label('مشاهدة→شراء')->suffix('%')->placeholder('—'),
                TextColumn::make('units')->label('قطع مباعة'),
                TextColumn::make('revenue')->label('المبيعات')->formatStateUsing(fn ($state) => \App\Support\TrackingReports::money($state))->color('success'),
                TextColumn::make('stock')->label('المخزون')->badge()->color(fn ($state) => $state === null ? 'gray' : ($state <= 3 ? 'danger' : 'gray')),
            ]);
    }
}
