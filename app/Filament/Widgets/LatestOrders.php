<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 2;

    protected static ?string $heading = 'أحدث الطلبات';

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query()->latest('id')->limit(8))
            ->paginated(false)
            ->columns([
                TextColumn::make('number')->label('الطلب')->weight('bold'),
                TextColumn::make('name')->label('العميل'),
                TextColumn::make('total')->label('الإجمالي')->numeric(3)->suffix(' د.ك'),
                TextColumn::make('status')->label('الحالة')->badge()->formatStateUsing(fn ($state) => OrderResource::STATUS[$state] ?? $state)->color(fn ($state) => OrderResource::STATUS_COLORS[$state] ?? 'gray'),
                TextColumn::make('created_at')->label('منذ')->since(),
            ])
            ->recordActions([
                Action::make('open')->label('عرض')->url(fn (Order $r) => OrderResource::getUrl('view', ['record' => $r])),
            ]);
    }
}
