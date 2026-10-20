<?php

namespace App\Filament\Resources\HeroSlides;

use App\Filament\Resources\HeroSlides\Pages\ManageHeroSlides;
use App\Filament\Support\Fields;
use App\Models\HeroSlide;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class HeroSlideResource extends Resource
{
    protected static ?string $model = HeroSlide::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'المحتوى';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'شريحة';

    protected static ?string $pluralModelLabel = 'سلايدر الرئيسية';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            ...Fields::bilingual('badge', 'الشارة'),
            ...Fields::bilingual('title', 'العنوان', required: true),
            ...Fields::bilingual('desc', 'الوصف', textarea: true),
            ...Fields::bilingual('btn', 'نص الزر'),
            TextInput::make('link')->label('رابط الزر')->placeholder('/products')->extraInputAttributes(['dir' => 'ltr']),
            Select::make('theme')->label('ألوان الشريحة')->options(['slate' => 'داكن + زر أزرق', 'sky' => 'أزرق + زر وردي', 'indigo' => 'نيلي + زر أصفر'])->default('slate')->required(),
            TextInput::make('sort')->label('الترتيب')->integer()->default(0),
            Toggle::make('is_active')->label('ظاهرة')->default(true)->inline(false),
            Fields::image('slides', size: '1920 × 600 بكسل (عريضة) — اجعل العنصر المهم في منتصف الصورة')->required(fn (?HeroSlide $record) => blank($record?->image))->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort')->reorderable('sort')->columns([
            ImageColumn::make('image')->label('')->getStateUsing(fn (HeroSlide $r) => $r->image_url)->imageWidth(120)->imageHeight(60),
            TextColumn::make('title_ar')->label('العنوان')->wrap()->description(fn (HeroSlide $r) => $r->badge_ar),
            ToggleColumn::make('is_active')->label('ظاهرة'),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageHeroSlides::route('/')];
    }
}
