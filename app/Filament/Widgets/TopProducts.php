<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TopProducts extends TableWidget
{
    protected static ?int $sort = 6;

    protected static ?string $heading = 'الأكثر مبيعاً';

    public function table(Table $table): Table
    {
        return $table
            ->query(Product::query()->orderByDesc('sales_count')->limit(6))
            ->paginated(false)
            ->recordUrl(fn (Product $r) => ProductResource::getUrl('edit', ['record' => $r]))
            ->columns([
                ImageColumn::make('image')->label('')->getStateUsing(fn (Product $r) => $r->image_url)->square()->size(36),
                TextColumn::make('name_ar')->label('المنتج')->limit(28),
                TextColumn::make('sales_count')->label('مبيع')->badge()->color('success'),
                TextColumn::make('views_count')->label('مشاهدة'),
            ]);
    }
}
