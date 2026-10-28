<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Http\Controllers\CheckoutController;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'طلب';

    protected static ?string $pluralModelLabel = 'الطلبات';

    protected static ?string $recordTitleAttribute = 'number';

    public const STATUS = [
        'pending' => 'جديد',
        'processing' => 'قيد التحضير',
        'shipped' => 'قيد التوصيل',
        'delivered' => 'تم التسليم',
        'cancelled' => 'ملغي',
    ];

    public const STATUS_COLORS = [
        'pending' => 'gray', 'processing' => 'warning', 'shipped' => 'info', 'delivered' => 'success', 'cancelled' => 'danger',
    ];

    public const PAYMENT = ['pending' => 'بانتظار الدفع', 'paid' => 'مدفوع', 'failed' => 'فشل الدفع'];

    public const METHODS = ['knet' => 'كي نت', 'card' => 'فيزا / ماستر', 'applepay' => 'Apple Pay', 'cod' => 'عند الاستلام'];

    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'name', 'phone', 'email'];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Order::where('status', 'pending')->where(fn ($q) => $q->where('payment_method', 'cod')->orWhere('payment_status', 'paid'))->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'طلبات جديدة بانتظار التحضير';
    }

    public static function statusAction(): Action
    {
        return Action::make('status')
            ->label('تغيير الحالة')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('warning')
            ->fillForm(fn (Order $record) => ['status' => $record->status, 'payment_status' => $record->payment_status])
            ->schema([
                Select::make('status')->label('حالة الطلب')->options(self::STATUS)->required(),
                Select::make('payment_status')->label('حالة الدفع')->options(self::PAYMENT)->required(),
            ])
            ->action(function (Order $record, array $data) {
                if ($data['status'] === 'cancelled' && $record->status !== 'cancelled') {
                    CheckoutController::releaseOrder($record);
                }
                $record->update($data + ($data['payment_status'] === 'paid' && ! $record->paid_at ? ['paid_at' => now()] : []));
            })
            ->successNotificationTitle('تم تحديث الطلب');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make('بيانات العميل')->columnSpan(1)->schema([
                    TextEntry::make('name')->label('الاسم'),
                    TextEntry::make('phone')->label('النقال')->copyable()
                        ->url(fn (Order $r) => 'https://wa.me/'.preg_replace('/\D/', '', $r->phone), shouldOpenInNewTab: true),
                    TextEntry::make('email')->label('البريد')->placeholder('—'),
                    TextEntry::make('user.name')->label('حساب العميل')->placeholder('زائر (بدون حساب)'),
                ]),
                Section::make('عنوان التوصيل')->columnSpan(1)->schema([
                    TextEntry::make('country.name_ar')->label('الدولة'),
                    TextEntry::make('city')->label('المنطقة'),
                    TextEntry::make('address')->label('العنوان')->getStateUsing(fn (Order $r) => $r->addressLine()),
                    TextEntry::make('notes')->label('ملاحظات')->placeholder('لا توجد'),
                ]),
                Section::make('الطلب والدفع')->columnSpan(1)->schema([
                    TextEntry::make('status')->label('الحالة')->badge()->formatStateUsing(fn ($state) => self::STATUS[$state] ?? $state)->color(fn ($state) => self::STATUS_COLORS[$state] ?? 'gray'),
                    TextEntry::make('payment_method')->label('طريقة الدفع')->formatStateUsing(fn ($state) => self::METHODS[$state] ?? $state),
                    TextEntry::make('payment_status')->label('حالة الدفع')->badge()->formatStateUsing(fn ($state) => self::PAYMENT[$state] ?? $state)
                        ->color(fn ($state) => ['paid' => 'success', 'failed' => 'danger'][$state] ?? 'warning'),
                    TextEntry::make('payment_id')->label('رقم عملية MyFatoorah')->placeholder('—')->copyable(),
                    TextEntry::make('created_at')->label('تاريخ الطلب')->dateTime('Y/m/d h:i A', 'Asia/Kuwait'),
                ]),
            ]),
            Section::make('مصدر الطلب والتتبع')->columnSpanFull()->columns(4)->collapsible()->schema([
                TextEntry::make('last_touch')->label('آخر مصدر قبل الطلب')->getStateUsing(fn (Order $r) => $r->sourceLabel('last'))->placeholder('مباشر / بدون مصدر')->weight('bold'),
                TextEntry::make('first_touch')->label('أول مصدر جابه للمتجر')->getStateUsing(fn (Order $r) => $r->sourceLabel('first'))->placeholder('مباشر / بدون مصدر'),
                TextEntry::make('click_ids')->label('نقرة إعلان')->placeholder('لا يوجد')
                    ->getStateUsing(fn (Order $r) => collect(['fbclid' => 'Meta', 'ttclid' => 'TikTok', 'sccid' => 'Snapchat', 'gclid' => 'Google'])
                        ->filter(fn ($name, $key) => isset($r->utm_data['last'][$key]) || isset($r->utm_data['first'][$key]))->values()->all())->badge(),
                TextEntry::make('tracking_sent_at')->label('أُرسل الشراء للمنصات')->since()->placeholder('لم يرسل')
                    ->belowContent(fn (Order $r) => $r->browser_tracked_at ? 'وتسجل من المتصفح أيضاً' : null),
                TextEntry::make('journey')->label('رحلة العميل')->getStateUsing(fn (Order $r) => $r->visitor ? 'عرض كل خطوة قبل الطلب ←' : null)->placeholder('—')->color('primary')
                    ->url(fn (Order $r) => $r->visitor ? \App\Filament\Resources\VisitorProfiles\VisitorProfileResource::getUrl('view', ['record' => $r->visitor]) : null),
                TextEntry::make('event_id')->label('event_id')->copyable()->size('sm')->color('gray')->placeholder('—'),
            ]),
            Section::make('المنتجات')->columnSpanFull()->schema([
                RepeatableEntry::make('items')->label('')->columns(6)->schema([
                    ImageEntry::make('image')->label('')->getStateUsing(fn ($record) => $record->image_url)->imageSize(56),
                    TextEntry::make('name_ar')->label('المنتج')->columnSpan(2)->belowContent(fn ($record) => $record->options_ar),
                    TextEntry::make('qty')->label('الكمية'),
                    TextEntry::make('unit_price')->label('سعر الوحدة')->numeric(3)->suffix(' د.ك'),
                    TextEntry::make('total')->label('الإجمالي')->numeric(3)->suffix(' د.ك')->weight('bold'),
                ]),
            ]),
            Section::make('الحساب')->columnSpanFull()->columns(5)->schema([
                TextEntry::make('subtotal')->label('المجموع الفرعي')->numeric(3)->suffix(' د.ك'),
                TextEntry::make('discount')->label('الخصم')->numeric(3)->suffix(' د.ك')->color('success'),
                TextEntry::make('coupon_code')->label('كود الخصم')->placeholder('—'),
                TextEntry::make('shipping')->label('التوصيل')->numeric(3)->suffix(' د.ك'),
                TextEntry::make('total')->label('الإجمالي')->numeric(3)->suffix(' د.ك')->weight('bold')->color('primary'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->poll('30s')
            ->columns([
                TextColumn::make('number')->label('رقم الطلب')->searchable()->copyable()->weight('bold'),
                TextColumn::make('name')->label('العميل')->searchable(['name', 'phone', 'email'])->description(fn (Order $r) => $r->phone),
                TextColumn::make('country_code')->label('الدولة')->badge()->color('gray'),
                TextColumn::make('items_sum_qty')->label('القطع')->sum('items', 'qty'),
                TextColumn::make('total')->label('الإجمالي')->numeric(3)->suffix(' د.ك')->sortable(),
                TextColumn::make('payment_method')->label('الدفع')->formatStateUsing(fn ($state) => self::METHODS[$state] ?? $state)
                    ->description(fn (Order $r) => self::PAYMENT[$r->payment_status] ?? ''),
                TextColumn::make('status')->label('الحالة')->badge()->formatStateUsing(fn ($state) => self::STATUS[$state] ?? $state)->color(fn ($state) => self::STATUS_COLORS[$state] ?? 'gray'),
                TextColumn::make('created_at')->label('التاريخ')->dateTime('Y/m/d h:i A', 'Asia/Kuwait')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('الحالة')->options(self::STATUS)->multiple(),
                SelectFilter::make('payment_status')->label('حالة الدفع')->options(self::PAYMENT),
                SelectFilter::make('payment_method')->label('طريقة الدفع')->options(self::METHODS),
                Filter::make('created_at')->label('الفترة')->schema([
                    DatePicker::make('from')->label('من'),
                    DatePicker::make('until')->label('إلى'),
                ])->query(fn (Builder $q, array $data) => $q
                    ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                    ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->recordActions([
                ViewAction::make(),
                self::statusAction(),
                Action::make('invoice')->label('الفاتورة')->icon(Heroicon::OutlinedDocumentText)->color('gray')
                    ->url(fn (Order $r) => route('invoice.show', $r), shouldOpenInNewTab: true),
            ])
            ->toolbarActions([
                BulkActionGroup::make(collect(['processing', 'shipped', 'delivered'])->map(fn ($s) => BulkAction::make("mark_{$s}")
                    ->label('تحويل إلى: '.self::STATUS[$s])
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => $records->each->update(['status' => $s])))->all()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
