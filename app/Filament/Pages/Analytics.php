<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Analytics\ActiveVisitorsTable;
use App\Filament\Widgets\Analytics\BrowsersChart;
use App\Filament\Widgets\Analytics\DevicesChart;
use App\Filament\Widgets\Analytics\HourlyTrafficChart;
use App\Filament\Widgets\Analytics\SearchTermsTable;
use App\Filament\Widgets\Analytics\TopPagesTable;
use App\Filament\Widgets\Analytics\TrafficSourcesTable;
use App\Filament\Widgets\Analytics\TrafficStats;
use App\Filament\Widgets\VisitorsChart;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Analytics extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?string $navigationLabel = 'تحليل الزوار';

    protected static ?string $title = 'تحليل الزوار والنشاط المباشر';

    protected static ?int $navigationSort = 1;

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Livewire::make(TrafficStats::class),
            Livewire::make(ActiveVisitorsTable::class),
            Livewire::make(VisitorsChart::class),
            Grid::make(2)->schema([
                Livewire::make(DevicesChart::class),
                Livewire::make(BrowsersChart::class),
                Livewire::make(TopPagesTable::class),
                Livewire::make(TrafficSourcesTable::class),
            ]),
            Livewire::make(HourlyTrafficChart::class),
            Livewire::make(SearchTermsTable::class),
        ]);
    }
}
