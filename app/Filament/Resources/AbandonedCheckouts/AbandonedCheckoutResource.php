<?php

namespace App\Filament\Resources\AbandonedCheckouts;

use App\Filament\Resources\AbandonedCheckouts\Pages\ListAbandonedCheckouts;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\VisitorProfiles\VisitorProfileResource;
use App\Models\AbandonedCheckout;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Shoppers who typed their phone / email at checkout and did not finish — captured live,
 * before any order exists, so the store can follow up on WhatsApp within minutes.
 */
class AbandonedCheckoutResource extends Resource
{
    protected static ?string $model = AbandonedCheckout::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'سلوك العملاء';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'abandoned-checkouts';

    protected static ?string $modelLabel = 'دفع متروك';

    protected static ?string $pluralModelLabel = 'الدفعات المتروكة';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = AbandonedCheckout::where('status', 'open')->where('updated_at', '<=', now()->subMinutes(15))->where('created_at', '>=', now()->subDays(2))->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'دفعات متروكة آخر يومين بانتظار التواصل';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->poll('30s')
            ->columns([
                TextColumn::make('name')->label('العميل')->placeholder('بدون اسم')->weight('bold')
                    ->description(fn (AbandonedCheckout $r) => $r->phone ?? $r->email)->searchable(['name', 'phone', 'email']),
                TextColumn::make('products')->label('المنتجات')->getStateUsing(fn (AbandonedCheckout $r) => collect($r->items)->map(fn ($i) => $i['name'].' ×'.$i['qty'])->all())
                    ->listWithLineBreaks()->limitList(2)->expandableLimitedList(),
                TextColumn::make('subtotal')->label('القيمة')->formatStateUsing(fn ($state) => \App\Support\TrackingReports::money($state))->sortable()->weight('bold'),
                TextColumn::make('step')->label('وصل لـ')->badge()->color('gray')->formatStateUsing(fn ($state) => AbandonedCheckout::STEPS[$state] ?? $state),
                IconColumn::make('gift_wrap')->label('هدية')->boolean()->trueIcon('heroicon-o-gift')->falseIcon('')->trueColor('pink'),
                TextColumn::make('source')->label('المصدر')->getStateUsing(fn (AbandonedCheckout $r) => $r->utm_data['last']['source'] ?? null)->placeholder('مباشر')
                    ->description(fn (AbandonedCheckout $r) => $r->utm_data['last']['campaign'] ?? null),
                TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn ($state) => AbandonedCheckout::STATUSES[$state] ?? $state)->color(fn ($state) => AbandonedCheckout::STATUS_COLORS[$state] ?? 'gray')
                    ->description(fn (AbandonedCheckout $r) => $r->whatsapp_sent_at ? 'واتساب تلقائي '.$r->whatsapp_sent_at->diffForHumans() : null),
                TextColumn::make('updated_at')->label('آخر نشاط')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('الحالة')->options(AbandonedCheckout::STATUSES)->multiple()->default(['open', 'contacted']),
                SelectFilter::make('step')->label('المرحلة')->options(AbandonedCheckout::STEPS),
            ])
            ->recordActions([
                Action::make('whatsapp')->label('واتساب')->icon(Heroicon::OutlinedChatBubbleLeftRight)->color('success')
                    ->visible(fn (AbandonedCheckout $r) => (bool) $r->whatsappUrl())
                    ->url(fn (AbandonedCheckout $r) => $r->whatsappUrl(), shouldOpenInNewTab: true),
                Action::make('contacted')->label('تم التواصل')->icon(Heroicon::OutlinedCheck)->color('warning')
                    ->visible(fn (AbandonedCheckout $r) => $r->status === 'open')
                    ->action(fn (AbandonedCheckout $r) => $r->update(['status' => 'contacted', 'contacted_at' => now()])),
                ViewAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('العميل')->columns(2)->schema([
                TextEntry::make('name')->label('الاسم')->placeholder('—'),
                TextEntry::make('phone')->label('النقال')->placeholder('—')->copyable()->url(fn (AbandonedCheckout $r) => $r->whatsappUrl(), shouldOpenInNewTab: true),
                TextEntry::make('email')->label('البريد')->placeholder('—')->copyable(),
                TextEntry::make('city')->label('المنطقة')->placeholder('—')->getStateUsing(fn (AbandonedCheckout $r) => collect([$r->country_code, $r->city])->filter()->implode(' - ')),
                TextEntry::make('payment_method')->label('طريقة الدفع المختارة')->placeholder('لم يختر')->formatStateUsing(fn ($state) => OrderResource::METHODS[$state] ?? $state),
                TextEntry::make('coupon_code')->label('كود الخصم')->placeholder('—'),
                TextEntry::make('gift_message')->label('ملاحظة / كرت الهدية')->placeholder('—')->columnSpanFull(),
                TextEntry::make('restore')->label('رابط يرجّع السلة ويعبّي بياناته (صالح 14 يوم)')->getStateUsing(fn (AbandonedCheckout $r) => $r->restoreUrl())->copyable()->limit(60)->columnSpanFull(),
                TextEntry::make('journey')->label('رحلة الزائر')->getStateUsing(fn () => 'عرض كل اللي سواه بالموقع ←')
                    ->url(fn (AbandonedCheckout $r) => $r->visitor ? VisitorProfileResource::getUrl('view', ['record' => $r->visitor]) : null)->color('primary'),
                TextEntry::make('order.number')->label('الطلب')->placeholder('ما طلب للحين')
                    ->url(fn (AbandonedCheckout $r) => $r->order ? OrderResource::getUrl('view', ['record' => $r->order]) : null),
            ]),
            Section::make('السلة')->schema([
                RepeatableEntry::make('items')->hiddenLabel()->columns(4)->schema([
                    TextEntry::make('name')->label('المنتج')->columnSpan(2)->belowContent(fn ($state, $get) => $get('options')),
                    TextEntry::make('qty')->label('الكمية'),
                    TextEntry::make('unit_price')->label('السعر')->formatStateUsing(fn ($state) => \App\Support\TrackingReports::money($state)),
                ]),
                TextEntry::make('subtotal')->label('الإجمالي')->formatStateUsing(fn ($state) => \App\Support\TrackingReports::money($state))->weight('bold'),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAbandonedCheckouts::route('/')];
    }
}
