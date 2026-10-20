<?php

namespace App\Filament\Resources\ContentPages;

use App\Filament\Resources\ContentPages\Pages\EditContentPage;
use App\Filament\Resources\ContentPages\Pages\ListContentPages;
use App\Filament\Support\Fields;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class ContentPageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'المحتوى';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'content-pages';

    protected static ?string $modelLabel = 'صفحة';

    protected static ?string $pluralModelLabel = 'صفحات الموقع';

    protected const COLORS = ['sky' => 'أزرق', 'pink' => 'وردي', 'amber' => 'أصفر', 'emerald' => 'أخضر', 'purple' => 'بنفسجي', 'indigo' => 'نيلي'];

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('رأس الصفحة')->columns(2)->columnSpanFull()->schema([
                ...Fields::bilingual('badge', 'الشارة'),
                ...Fields::bilingual('title', 'العنوان', required: true),
                ...Fields::bilingual('subtitle', 'النص التعريفي', textarea: true),
                Fields::image('pages', label: 'صورة الخلفية (صفحة من نحن)', size: '1600 × 600 بكسل (عريضة)')->columnSpanFull(),
                Toggle::make('is_active')->label('الصفحة منشورة')->default(true),
            ]),
            Section::make('الأرقام والإحصائيات')->description('تظهر في صفحة "منو إحنا"')->columnSpanFull()
                ->visible(fn (?Page $record) => $record?->slug === 'about')->schema([
                    Repeater::make('stats')->label('')->columns(4)->reorderable()->addActionLabel('إضافة رقم')->schema([
                        TextInput::make('value')->label('الرقم')->required(),
                        Select::make('color')->label('اللون')->options(self::COLORS)->default('sky'),
                        TextInput::make('label_ar')->label('الوصف (عربي)')->required(),
                        TextInput::make('label_en')->label('الوصف (English)'),
                    ]),
                ]),
            Section::make('البطاقات / الفقرات')->columnSpanFull()->schema([
                Repeater::make('cards')->label('')->columns(2)->reorderable()->collapsible()->addActionLabel('إضافة بطاقة')
                    ->itemLabel(fn (array $state) => $state['title_ar'] ?? null)
                    ->schema([
                        Select::make('icon')->label('الأيقونة')->searchable()->placeholder('بدون أيقونة')
                            ->options(fn () => json_decode(file_get_contents(resource_path('icons.json')), true)),
                        Select::make('color')->label('اللون')->options(self::COLORS)->default('sky'),
                        TextInput::make('title_ar')->label('العنوان (عربي)')->required(),
                        TextInput::make('title_en')->label('العنوان (English)'),
                        \Filament\Forms\Components\Textarea::make('body_ar')->label('النص (عربي)')->rows(3),
                        \Filament\Forms\Components\Textarea::make('body_en')->label('النص (English)')->rows(3),
                    ]),
            ]),
            Fields::seoSection(),
            Section::make('محتوى إضافي حر')->columnSpanFull()->collapsed()->schema([
                RichEditor::make('body_ar')->label('المحتوى (عربي)'),
                RichEditor::make('body_en')->label('المحتوى (English)'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title_ar')->label('الصفحة')->description(fn (Page $r) => '/'.$r->slug),
            TextColumn::make('updated_at')->label('آخر تعديل')->since(),
            ToggleColumn::make('is_active')->label('منشورة'),
        ])->recordActions([
            EditAction::make(),
            Action::make('view')->label('عرض')->icon(Heroicon::OutlinedEye)->color('gray')->url(fn (Page $r) => url('/'.$r->slug), shouldOpenInNewTab: true),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContentPages::route('/'),
            'edit' => EditContentPage::route('/{record}/edit'),
        ];
    }
}
