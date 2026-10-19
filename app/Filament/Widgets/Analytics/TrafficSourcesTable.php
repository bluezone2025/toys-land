<?php

namespace App\Filament\Widgets\Analytics;

use App\Support\Analytics;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TrafficSourcesTable extends TableWidget
{
    protected static ?string $heading = 'مصادر الزيارات (30 يوم)';

    public function table(Table $table): Table
    {
        return $table
            ->records(function () {
                $rows = collect(Analytics::countBy('referrer', 30, 15))
                    ->mapWithKeys(fn ($c, $host) => [$host => ['source' => $host, 'visitors' => $c]]);
                $direct = \App\Models\Visit::where('created_at', '>=', now()->subDays(30))->whereNull('referrer')->distinct()->count('session_id');

                return $rows->prepend(['source' => 'مباشر / بدون مصدر', 'visitors' => $direct], 'direct')->sortByDesc('visitors')->all();
            })
            ->paginated(false)
            ->columns([
                TextColumn::make('source')->label('المصدر'),
                TextColumn::make('visitors')->label('زوار')->badge(),
            ]);
    }
}
