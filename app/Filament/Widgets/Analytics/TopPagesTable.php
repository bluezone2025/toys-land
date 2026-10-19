<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Visit;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TopPagesTable extends TableWidget
{
    protected static ?string $heading = 'أكثر الصفحات زيارة (30 يوم)';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => Visit::where('created_at', '>=', now()->subDays(30))
                ->selectRaw('path, COUNT(*) as views, COUNT(DISTINCT session_id) as visitors')
                ->groupBy('path')->orderByDesc('views')->limit(15)->get()
                ->mapWithKeys(fn ($r) => [$r->path => ['path' => $r->path, 'views' => (int) $r->views, 'visitors' => (int) $r->visitors]])
                ->all())
            ->paginated(false)
            ->columns([
                TextColumn::make('path')->label('الصفحة')->limit(45),
                TextColumn::make('visitors')->label('زوار'),
                TextColumn::make('views')->label('مشاهدات')->badge(),
            ]);
    }
}
