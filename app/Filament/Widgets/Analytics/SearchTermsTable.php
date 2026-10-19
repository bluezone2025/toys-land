<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\AnalyticsEvent;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class SearchTermsTable extends TableWidget
{
    protected static ?string $heading = 'كلمات البحث بالمتجر (30 يوم)';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => AnalyticsEvent::where('type', 'search')->where('created_at', '>=', now()->subDays(30))
                ->latest('id')->limit(500)->get()
                ->countBy(fn ($e) => mb_strtolower(trim($e->meta['q'] ?? '')))
                ->filter(fn ($c, $q) => $q !== '')->sortDesc()->take(15)
                ->mapWithKeys(fn ($c, $q) => [$q => ['q' => $q, 'count' => $c]])->all())
            ->paginated(false)
            ->emptyStateHeading('ما في عمليات بحث للحين')
            ->columns([
                TextColumn::make('q')->label('كلمة البحث'),
                TextColumn::make('count')->label('مرات')->badge(),
            ]);
    }
}
