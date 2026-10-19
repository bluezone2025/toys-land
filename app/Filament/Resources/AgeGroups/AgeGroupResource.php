<?php

namespace App\Filament\Resources\AgeGroups;

use App\Filament\Resources\AgeGroups\Pages\ManageAgeGroups;
use App\Filament\Support\Fields;
use App\Models\AgeGroup;
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
use Filament\Tables\Table;
use UnitEnum;

class AgeGroupResource extends Resource
{
    protected static ?string $model = AgeGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCake;

    protected static string|UnitEnum|null $navigationGroup = 'المتجر';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'فئة عمرية';

    protected static ?string $pluralModelLabel = 'الفئات العمرية';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('key')->label('الرمز (مثال: 3-5)')->required()->unique(ignoreRecord: true)->maxLength(10),
            Select::make('color')->label('اللون')->options(['pink' => 'وردي', 'amber' => 'أصفر', 'sky' => 'أزرق', 'purple' => 'بنفسجي', 'emerald' => 'أخضر', 'indigo' => 'نيلي'])->default('sky')->required(),
            ...Fields::bilingual('title', 'العنوان', required: true),
            ...Fields::bilingual('desc', 'الوصف'),
            TextInput::make('sort')->label('الترتيب')->integer()->default(0),
            Toggle::make('is_active')->label('ظاهر')->default(true)->inline(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort')->reorderable('sort')->columns([
            TextColumn::make('key')->label('الرمز')->badge(),
            TextColumn::make('title_ar')->label('العنوان')->description(fn (AgeGroup $r) => $r->desc_ar),
            TextColumn::make('products_count')->label('المنتجات')->counts('products'),
            ToggleColumn::make('is_active')->label('ظاهر'),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAgeGroups::route('/')];
    }
}
