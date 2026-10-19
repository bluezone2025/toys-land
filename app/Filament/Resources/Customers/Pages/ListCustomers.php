<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('إضافة مستخدم')];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل'),
            'buyers' => Tab::make('اشتروا من المتجر')->modifyQueryUsing(fn (Builder $q) => $q->has('orders')),
            'active' => Tab::make('نشطين آخر 30 يوم')->modifyQueryUsing(fn (Builder $q) => $q->where('last_seen_at', '>=', now()->subDays(30))),
            'admins' => Tab::make('المدراء')->modifyQueryUsing(fn (Builder $q) => $q->where('is_admin', true)),
        ];
    }
}
