<?php

namespace App\Filament\Widgets\Tracking;

use App\Filament\Resources\VisitorProfiles\VisitorProfileResource;
use App\Models\AnalyticsEvent;
use App\Support\TrackingReports;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Everything shoppers do, as it happens. */
class LiveEventsFeed extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = '⚡ النشاط المباشر — كل حركة يسويها الزوار';

    public function table(Table $table): Table
    {
        return $table
            ->query(AnalyticsEvent::query()->with(['visitor', 'product:id,name_ar,slug'])->where('created_at', '>=', now()->subDay()))
            ->defaultSort('id', 'desc')
            ->poll('5s')
            ->paginated([15, 30, 50])
            ->emptyStateHeading('ما في نشاط للحين')
            ->recordUrl(fn (AnalyticsEvent $r) => $r->visitor ? VisitorProfileResource::getUrl('view', ['record' => $r->visitor]) : null)
            ->columns([
                TextColumn::make('created_at')->label('الوقت')->since()->size('sm'),
                TextColumn::make('visitor')->label('الزائر')
                    ->getStateUsing(fn (AnalyticsEvent $r) => $r->visitor?->displayName() ?? 'زائر')
                    ->description(fn (AnalyticsEvent $r) => $r->visitor?->phone ?? $r->visitor?->last_source),
                TextColumn::make('type')->label('الحركة')->formatStateUsing(fn ($state) => TrackingReports::label($state))->weight('bold'),
                TextColumn::make('detail')->label('التفاصيل')->wrap()->getStateUsing(fn (AnalyticsEvent $r) => $r->product?->name_ar
                    ?? ($r->meta['search_term'] ?? $r->meta['item_list_name'] ?? $r->meta['payment_type'] ?? $r->meta['shipping_tier'] ?? $r->meta['transaction_id'] ?? null))
                    ->placeholder('—'),
                TextColumn::make('value')->label('القيمة')->formatStateUsing(fn ($state) => \App\Support\TrackingReports::money($state))->placeholder('—'),
                TextColumn::make('path')->label('الصفحة')->limit(35)->color('gray')->size('sm'),
                TextColumn::make('dispatch_status')->label('الإعلانات')->badge()
                    ->formatStateUsing(fn ($state) => ['sent' => 'وصل', 'pending' => 'بالطريق', 'failed' => 'فشل', 'skipped' => '—'][$state] ?? $state)
                    ->color(fn ($state) => ['sent' => 'success', 'pending' => 'warning', 'failed' => 'danger'][$state] ?? 'gray')
                    ->tooltip(fn (AnalyticsEvent $r) => $r->delivery ? collect($r->delivery)->map(fn ($s, $p) => "{$p}: {$s}")->implode(' · ') : null)
                    ->placeholder('—'),
            ]);
    }
}
