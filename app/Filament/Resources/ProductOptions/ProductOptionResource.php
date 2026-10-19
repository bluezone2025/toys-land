<?php

namespace App\Filament\Resources\ProductOptions;

use App\Filament\Resources\ProductOptions\Pages\ManageProductOptions;
use App\Filament\Support\Fields;
use App\Models\ProductOption;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ProductOptionResource extends Resource
{
    protected static ?string $model = ProductOption::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'المتجر';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'إصدار / إضافة';

    protected static ?string $pluralModelLabel = 'الإصدارات والإضافات';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('type')->label('النوع')->options(['edition' => 'إصدار اللعبة (اختيار واحد)', 'addon' => 'إضافة / تغليف (اختيار متعدد)'])->required()->default('addon'),
            TextInput::make('price')->label('السعر الإضافي (د.ك)')->numeric()->minValue(0)->step(0.001)->default(0)->required()
                ->helperText('الإصدار الأساسي يكون سعره 0'),
            ...Fields::bilingual('name', 'الاسم', required: true),
            TextInput::make('icon')->label('أيقونة (إيموجي)')->maxLength(10),
            TextInput::make('sort')->label('الترتيب')->integer()->default(0),
            Toggle::make('is_active')->label('مفعل')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort')->reorderable('sort')->columns([
            TextColumn::make('type')->label('النوع')->badge()->formatStateUsing(fn ($state) => $state === 'edition' ? 'إصدار' : 'إضافة')
                ->color(fn ($state) => $state === 'edition' ? 'info' : 'success'),
            TextColumn::make('name_ar')->label('الاسم')->formatStateUsing(fn ($state, ProductOption $r) => trim($r->icon.' '.$state))->description(fn (ProductOption $r) => $r->name_en),
            TextColumn::make('price')->label('السعر الإضافي')->numeric(3)->suffix(' د.ك'),
            ToggleColumn::make('is_active')->label('مفعل'),
        ])->filters([
            SelectFilter::make('type')->label('النوع')->options(['edition' => 'إصدار', 'addon' => 'إضافة']),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageProductOptions::route('/')];
    }
}
