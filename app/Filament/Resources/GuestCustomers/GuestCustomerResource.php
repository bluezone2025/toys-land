<?php

namespace App\Filament\Resources\GuestCustomers;

use App\Filament\Resources\GuestCustomers\Pages\ListGuestCustomers;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\GuestCustomer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class GuestCustomerResource extends Resource
{
    protected static ?string $model = GuestCustomer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'guest-buyers';

    protected static ?string $modelLabel = 'مشتري زائر';

    protected static ?string $pluralModelLabel = 'المشترين بدون حساب';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_order_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('الاسم')->searchable(['name', 'email', 'phone'])->weight('bold'),
                TextColumn::make('email')->label('البريد')->copyable(),
                TextColumn::make('phone')->label('النقال')->copyable()
                    ->url(fn (GuestCustomer $r) => 'https://wa.me/'.preg_replace('/\D/', '', $r->phone), shouldOpenInNewTab: true),
                TextColumn::make('country_code')->label('الدولة')->badge()->color('gray'),
                TextColumn::make('city')->label('المنطقة')->description(fn (GuestCustomer $r) => $r->address)->toggleable(),
                TextColumn::make('orders_count')->label('الطلبات')->badge()->sortable(),
                TextColumn::make('total_spent')->label('إجمالي المشتريات')->numeric(3)->suffix(' د.ك')->sortable(),
                TextColumn::make('last_order_at')->label('آخر طلب')->since()->sortable(),
                IconColumn::make('unsubscribed_at')->label('يستقبل العروض')->boolean()->getStateUsing(fn (GuestCustomer $r) => ! $r->unsubscribed_at),
            ])
            ->recordActions([
                Action::make('orders')->label('طلباته')->icon(Heroicon::OutlinedShoppingBag)->color('gray')
                    ->url(fn (GuestCustomer $r) => OrderResource::getUrl('index', ['tableSearch' => $r->email])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListGuestCustomers::route('/')];
    }
}
