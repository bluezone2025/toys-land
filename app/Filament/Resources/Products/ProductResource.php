<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Support\Fields;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $recordRouteKeyName = 'id';

    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static string|UnitEnum|null $navigationGroup = 'المتجر';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'منتج';

    protected static ?string $pluralModelLabel = 'المنتجات';

    protected static ?string $recordTitleAttribute = 'name_ar';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name_ar', 'name_en', 'sku'];
    }

    public static function getNavigationBadge(): ?string
    {
        $low = Product::where('is_active', true)->where('stock', '<=', 3)->count();

        return $low ? (string) $low : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'منتجات مخزونها 3 قطع أو أقل';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make('بيانات المنتج')->columnSpan(2)->columns(2)->schema([
                    TextInput::make('name_ar')->label('الاسم (عربي)')->required()->maxLength(255),
                    TextInput::make('name_en')->label('الاسم (English)')->required()->maxLength(255)->extraInputAttributes(['dir' => 'ltr'])
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                    ...Fields::bilingual('desc', 'الوصف', textarea: true),
                    ...Fields::bilingual('badge', 'الشارة (مثال: الأكثر طلباً 🚀)'),
                    TextInput::make('slug')->label('رابط المنتج (slug)')->required()->unique(ignoreRecord: true)->alphaDash()->maxLength(255)->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('sku')->label('رمز المنتج SKU')->unique(ignoreRecord: true)->maxLength(100),
                ]),
                Section::make('السعر والمخزون')->columnSpan(1)->schema([
                    TextInput::make('price')->label('السعر (د.ك)')->required()->numeric()->minValue(0)->step(0.001)->suffix('KWD'),
                    TextInput::make('compare_price')->label('السعر قبل الخصم')->numeric()->minValue(0)->step(0.001)->suffix('KWD')
                        ->helperText('اختياري: يظهر مشطوباً بجانب السعر'),
                    TextInput::make('stock')->label('الكمية بالمخزون')->required()->integer()->minValue(0)->default(0),
                    Select::make('category_id')->label('القسم')->relationship('category', 'title_ar')->searchable()->preload()->required(),
                    Select::make('age_group_id')->label('الفئة العمرية')->relationship('ageGroup', 'title_ar')->preload(),
                    Toggle::make('is_active')->label('ظاهر بالمتجر')->default(true),
                    Toggle::make('is_best_seller')->label('ضمن "الأكثر طلباً" بالرئيسية'),
                    Toggle::make('has_options')->label('يقبل الإصدارات والإضافات')->default(true),
                    TextInput::make('sort')->label('الترتيب')->integer()->default(0),
                ]),
            ]),
            Section::make('الصور')->columnSpanFull()->columns(2)->schema([
                Fields::image('products')->label('الصورة الرئيسية')->required(fn (?Product $record) => blank($record?->image)),
                FileUpload::make('gallery')->label('صور إضافية')->image()->multiple()->reorderable()->disk('public')->directory('products/gallery')->maxFiles(8)->maxSize(4096),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                ImageColumn::make('image')->label('')->getStateUsing(fn (Product $r) => $r->image_url)->square()->size(48),
                TextColumn::make('name_ar')->label('المنتج')->searchable(['name_ar', 'name_en', 'sku'])->sortable()
                    ->description(fn (Product $r) => $r->name_en)->wrap(),
                TextColumn::make('category.title_ar')->label('القسم')->badge()->sortable(),
                TextColumn::make('ageGroup.key')->label('العمر')->badge()->color('gray'),
                TextColumn::make('price')->label('السعر')->numeric(3)->suffix(' د.ك')->sortable(),
                TextColumn::make('stock')->label('المخزون')->sortable()->badge()
                    ->color(fn (int $state) => $state <= 0 ? 'danger' : ($state <= 3 ? 'warning' : 'success')),
                TextColumn::make('sales_count')->label('المبيعات')->sortable(),
                TextColumn::make('views_count')->label('المشاهدات')->sortable()->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_best_seller')->label('الأكثر طلباً'),
                ToggleColumn::make('is_active')->label('ظاهر'),
                TextColumn::make('created_at')->label('أضيف في')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')->label('القسم')->relationship('category', 'title_ar'),
                SelectFilter::make('age_group_id')->label('الفئة العمرية')->relationship('ageGroup', 'title_ar'),
                TernaryFilter::make('is_active')->label('ظاهر بالمتجر'),
                Filter::make('low_stock')->label('مخزون منخفض (3 أو أقل)')->query(fn (Builder $q) => $q->where('stock', '<=', 3)),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()->label('نسخ')
                    ->mutateRecordDataUsing(fn (array $data) => [...$data, 'slug' => $data['slug'].'-'.Str::lower(Str::random(4)), 'sku' => null, 'sales_count' => 0, 'views_count' => 0]),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')->label('إظهار المحدد')->icon(Heroicon::OutlinedEye)
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true])),
                    BulkAction::make('deactivate')->label('إخفاء المحدد')->icon(Heroicon::OutlinedEyeSlash)
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false])),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
