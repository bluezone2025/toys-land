<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        $tabs = ['all' => Tab::make('الكل')];
        foreach (OrderResource::STATUS as $key => $label) {
            $tabs[$key] = Tab::make($label)->modifyQueryUsing(fn (Builder $q) => $q->where('status', $key));
        }

        return $tabs;
    }
}
