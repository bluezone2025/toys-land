<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Support\Fields;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'المتجر';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'قسم';

    protected static ?string $pluralModelLabel = 'الأقسام';

    protected static ?string $recordTitleAttribute = 'title_ar';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->columnSpanFull()->schema([
                ...Fields::bilingual('title', 'اسم القسم', required: true),
                ...Fields::bilingual('desc', 'وصف مختصر'),
                ...Fields::bilingual('badge', 'الشارة'),
                TextInput::make('slug')->label('الرابط (slug)')->required()->unique(ignoreRecord: true)->alphaDash()->extraInputAttributes(['dir' => 'ltr']),
                Select::make('home_group')->label('يظهر في سلايدر الرئيسية')->options([
                    'edu' => 'ألعاب الذكاء والعلوم والمكعبات',
                    'fun' => 'عالم الدمى والسيارات والمغامرات',
                ])->placeholder('لا يظهر'),
                TextInput::make('sort')->label('الترتيب')->integer()->default(0),
                Toggle::make('is_active')->label('ظاهر')->default(true)->inline(false),
                Fields::image('categories')->required(fn (?Category $record) => blank($record?->image))->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                ImageColumn::make('image')->label('')->getStateUsing(fn (Category $r) => $r->image_url)->square()->size(44),
                TextColumn::make('title_ar')->label('القسم')->description(fn (Category $r) => $r->title_en)->searchable(['title_ar', 'title_en']),
                TextColumn::make('products_count')->label('عدد المنتجات')->counts('products')->badge(),
                TextColumn::make('home_group')->label('سلايدر الرئيسية')->formatStateUsing(fn (?string $state) => ['edu' => 'الذكاء والعلوم', 'fun' => 'الدمى والمغامرات'][$state] ?? '—'),
                ToggleColumn::make('is_active')->label('ظاهر'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
