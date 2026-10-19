<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Support\Collection;

/**
 * Session cart. Only product/option ids and quantities are stored; prices are always re-read from the database.
 */
class Cart
{
    public const KEY = 'cart';

    protected static ?Collection $items = null;

    public static function raw(): array
    {
        return session(self::KEY, []);
    }

    public static function add(Product $product, array $optionIds = [], int $qty = 1): void
    {
        $optionIds = static::validOptionIds($product, $optionIds);
        sort($optionIds);
        $key = md5($product->id.'|'.implode(',', $optionIds));

        $cart = static::raw();
        $cart[$key] = [
            'product_id' => $product->id,
            'options' => $optionIds,
            'qty' => ($cart[$key]['qty'] ?? 0) + max(1, $qty),
        ];
        static::save($cart);
    }

    public static function changeQty(string $key, int $delta): void
    {
        $cart = static::raw();
        if (! isset($cart[$key])) {
            return;
        }
        $cart[$key]['qty'] += $delta;
        if ($cart[$key]['qty'] <= 0) {
            unset($cart[$key]);
        }
        static::save($cart);
    }

    public static function remove(string $key): void
    {
        $cart = static::raw();
        unset($cart[$key]);
        static::save($cart);
    }

    public static function clear(): void
    {
        static::save([]);
    }

    public static function count(): int
    {
        return (int) array_sum(array_column(static::raw(), 'qty'));
    }

    /**
     * @return Collection<int, object{key: string, product: Product, options: Collection, unit_price: float, qty: int, total: float}>
     */
    public static function items(): Collection
    {
        if (static::$items !== null) {
            return static::$items;
        }

        $raw = static::raw();
        $products = Product::active()->whereIn('id', array_column($raw, 'product_id'))->get()->keyBy('id');
        $options = ProductOption::active()->get()->keyBy('id');

        return static::$items = collect($raw)->map(function ($row, $key) use ($products, $options) {
            $product = $products[$row['product_id']] ?? null;
            if (! $product) {
                return null;
            }
            $selected = collect($row['options'])->map(fn ($id) => $options[$id] ?? null)->filter()->values();
            $unit = (float) $product->price + (float) $selected->sum('price');

            return (object) [
                'key' => $key,
                'product' => $product,
                'options' => $selected,
                'unit_price' => round($unit, 3),
                'qty' => (int) $row['qty'],
                'total' => round($unit * $row['qty'], 3),
            ];
        })->filter()->values();
    }

    public static function subtotal(): float
    {
        return round((float) static::items()->sum('total'), 3);
    }

    /** "Standard Edition | Gift Wrap, Batteries" in the given locale. */
    public static function optionsLabel(Collection $options, string $locale): string
    {
        $editions = $options->where('type', 'edition')->map(fn ($o) => $o->{"name_{$locale}"});
        $addons = $options->where('type', 'addon')->map(fn ($o) => $o->{"name_{$locale}"});
        if ($editions->isEmpty()) {
            $default = ProductOption::active()->where('type', 'edition')->where('price', 0)->first();
            $editions = collect([$default?->{"name_{$locale}"} ?? ($locale === 'ar' ? 'الإصدار العادي' : 'Standard Edition')]);
        }

        return $editions->implode(', ').($addons->isNotEmpty() ? ' | '.$addons->implode(', ') : '');
    }

    protected static function validOptionIds(Product $product, array $ids): array
    {
        if (! $product->has_options || ! $ids) {
            return [];
        }
        $options = ProductOption::active()->whereIn('id', $ids)->get();
        // Only one edition may be chosen; any number of add-ons.
        $edition = $options->where('type', 'edition')->first();

        return $options->where('type', 'addon')->pluck('id')
            ->when($edition && (float) $edition->price > 0, fn ($c) => $c->push($edition->id))
            ->map(fn ($id) => (int) $id)->values()->all();
    }

    protected static function save(array $cart): void
    {
        session([self::KEY => $cart]);
        static::$items = null;
    }
}
