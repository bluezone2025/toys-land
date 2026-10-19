<?php

namespace App\Filament\Resources\AbandonedCarts;

use App\Filament\Resources\AbandonedCarts\Pages\ListAbandonedCarts;
use App\Models\SavedCart;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AbandonedCartResource extends Resource
{
    protected static ?string $model = SavedCart::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'abandoned-carts';

    protected static ?string $modelLabel = 'سلة متروكة';

    protected static ?string $pluralModelLabel = 'السلات المتروكة';

    public static function canCreate(): bool
    {
        return false;
    }

    /** Non-empty carts untouched for at least an hour. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user')->where('items_count', '>', 0)->where('updated_at', '<=', now()->subHour());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('العميل')->placeholder('زائر (بدون حساب)')->description(fn (SavedCart $r) => $r->user?->email)->searchable(),
                TextColumn::make('products')->label('المنتجات')->getStateUsing(fn (SavedCart $r) => $r->lines()->map(fn ($l) => $l['product']->name_ar.' ×'.$l['qty'])->all())->listWithLineBreaks()->limitList(3)->expandableLimitedList(),
                TextColumn::make('items_count')->label('القطع')->badge(),
                TextColumn::make('subtotal')->label('القيمة')->numeric(3)->suffix(' د.ك')->sortable(),
                TextColumn::make('updated_at')->label('آخر تعديل')->since()->sortable(),
                TextColumn::make('reminded_at')->label('التذكير')->since()->placeholder('لم يُرسل'),
            ])
            ->filters([
                TernaryFilter::make('user_id')->label('نوع العميل')->nullable()->trueLabel('مسجّلين')->falseLabel('زوار'),
            ])
            ->recordActions([
                Action::make('whatsapp')->label('واتساب')->icon(Heroicon::OutlinedChatBubbleLeftRight)->color('success')
                    ->visible(fn (SavedCart $r) => filled($r->user?->phone))
                    ->url(fn (SavedCart $r) => 'https://wa.me/'.preg_replace('/\D/', '', (string) $r->user?->phone).'?text='.urlencode('هلا '.$r->user?->name.'، لاحظنا إن عندك ألعاب في السلة بمتجر تويز لاند 🧸 تحب نساعدك تكمل طلبك؟ '.route('cart')), shouldOpenInNewTab: true),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAbandonedCarts::route('/')];
    }
}
