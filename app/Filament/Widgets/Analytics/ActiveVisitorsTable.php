<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Visit;
use App\Support\Analytics;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ActiveVisitorsTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = '🟢 الزوار المتواجدين الآن';

    public function table(Table $table): Table
    {
        $since = now()->subMinutes(Analytics::ACTIVE_MINUTES);

        return $table
            ->query(Visit::query()->with('user')->whereIn('id', Visit::where('created_at', '>=', $since)->selectRaw('MAX(id)')->groupBy('session_id')))
            ->defaultSort('id', 'desc')
            ->poll('10s')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('ما في زوار حالياً')
            ->columns([
                TextColumn::make('user.name')->label('الزائر')->placeholder('زائر')->description(fn (Visit $r) => $r->ip),
                TextColumn::make('path')->label('الصفحة الحالية')->limit(50)
                    ->url(fn (Visit $r) => url($r->path), shouldOpenInNewTab: true),
                TextColumn::make('device')->label('الجهاز')->badge()->formatStateUsing(fn ($state) => ['mobile' => '📱 جوال', 'tablet' => '📲 تابلت', 'desktop' => '💻 كمبيوتر'][$state] ?? $state),
                TextColumn::make('browser')->label('المتصفح')->description(fn (Visit $r) => $r->platform),
                TextColumn::make('referrer')->label('المصدر')->placeholder('مباشر'),
                TextColumn::make('pages')->label('صفحات بالجلسة')->getStateUsing(fn (Visit $r) => Visit::where('session_id', $r->session_id)->count()),
                TextColumn::make('created_at')->label('آخر نشاط')->since(),
            ]);
    }
}
