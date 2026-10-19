<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'طلبات العميل';

    public function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('number')->label('رقم الطلب'),
            TextColumn::make('total')->label('الإجمالي')->numeric(3)->suffix(' د.ك'),
            TextColumn::make('status')->label('الحالة')->badge()->formatStateUsing(fn ($state) => OrderResource::STATUS[$state] ?? $state)->color(fn ($state) => OrderResource::STATUS_COLORS[$state] ?? 'gray'),
            TextColumn::make('created_at')->label('التاريخ')->date('Y/m/d'),
        ])->recordActions([
            ViewAction::make()->url(fn ($record) => OrderResource::getUrl('view', ['record' => $record])),
        ]);
    }
}
