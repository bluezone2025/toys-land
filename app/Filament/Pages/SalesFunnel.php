<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Tracking\AttributionTable;
use App\Filament\Widgets\Tracking\FunnelChart;
use App\Filament\Widgets\Tracking\FunnelStats;
use App\Filament\Widgets\Tracking\ListPerformanceTable;
use App\Filament\Widgets\Tracking\ProductPerformanceTable;
use App\Filament\Widgets\Tracking\RecoveryStats;
use App\Filament\Widgets\Analytics\SearchTermsTable;
use App\Support\TrackingReports;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Funnel, attribution, product and list performance for a chosen period. */
class SalesFunnel extends BaseDashboard
{
    use HasFiltersForm;

    protected static string $routePath = 'funnel';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFunnel;

    protected static string|UnitEnum|null $navigationGroup = 'العملاء والتتبع';

    protected static ?string $navigationLabel = 'مسار الشراء والتسويق';

    protected static ?string $title = 'مسار الشراء ومصادر المبيعات';

    protected static ?int $navigationSort = 2;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('period')->label('الفترة')->options(TrackingReports::PERIODS)->default('30')->selectablePlaceholder(false),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            FunnelStats::class,
            FunnelChart::class,
            AttributionTable::class,
            ProductPerformanceTable::class,
            RecoveryStats::class,
            ListPerformanceTable::class,
            SearchTermsTable::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
