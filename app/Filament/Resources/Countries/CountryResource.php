<?php

namespace App\Filament\Resources\Countries;

use App\Filament\Resources\Countries\Pages\ManageCountries;
use App\Filament\Support\Fields;
use App\Models\Country;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use UnitEnum;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAsiaAustralia;

    protected static string|UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'دولة';

    protected static ?string $pluralModelLabel = 'الدول والعملات والشحن';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('code')->label('رمز الدولة (ISO مثل KW)')->required()->length(2)->unique(ignoreRecord: true)
                ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
            TextInput::make('flag')->label('العلم (إيموجي)')->maxLength(10),
            ...Fields::bilingual('name', 'اسم الدولة', required: true),
            ...Fields::bilingual('symbol', 'رمز العملة', required: true),
            TextInput::make('rate')->label('سعر الصرف مقابل 1 د.ك')->numeric()->minValue(0.0001)->step(0.0001)->required(),
            TextInput::make('shipping_fee')->label('رسوم الشحن (د.ك)')->numeric()->minValue(0)->step(0.001)->required()
                ->helperText('تُلغى تلقائياً إذا تجاوز الطلب حد الشحن المجاني من الإعدادات'),
            ...Fields::bilingual('delivery', 'مدة التوصيل'),
            TextInput::make('sort')->label('الترتيب')->integer()->default(0),
            Toggle::make('is_active')->label('مفعلة')->default(true)->inline(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort')->reorderable('sort')->columns([
            TextColumn::make('name_ar')->label('الدولة')->formatStateUsing(fn ($state, Country $r) => $r->flag.' '.$state),
            TextColumn::make('rate')->label('سعر الصرف')->formatStateUsing(fn ($state, Country $r) => '1 د.ك = '.(float) $state.' '.$r->symbol_ar),
            TextColumn::make('shipping_fee')->label('الشحن')->numeric(3)->suffix(' د.ك'),
            TextColumn::make('delivery_ar')->label('مدة التوصيل'),
            ToggleColumn::make('is_active')->label('مفعلة')->afterStateUpdated(fn () => Cache::forget('countries')),
        ])->recordActions([EditAction::make()->after(fn () => Cache::forget('countries'))]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCountries::route('/')];
    }
}
