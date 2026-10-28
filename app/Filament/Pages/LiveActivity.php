<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Analytics\ActiveVisitorsTable;
use App\Filament\Widgets\Tracking\HotLeads;
use App\Filament\Widgets\Tracking\LiveEventsFeed;
use App\Filament\Widgets\Tracking\LiveStats;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class LiveActivity extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|UnitEnum|null $navigationGroup = 'العملاء والتتبع';

    protected static ?string $navigationLabel = 'النشاط المباشر';

    protected static ?string $title = 'النشاط المباشر في المتجر';

    protected static ?string $slug = 'live';

    protected static ?int $navigationSort = 1;

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Livewire::make(LiveStats::class),
            Livewire::make(LiveEventsFeed::class),
            Livewire::make(HotLeads::class),
            Livewire::make(ActiveVisitorsTable::class),
        ]);
    }
}
