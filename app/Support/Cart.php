<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\SavedCart;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Support\Collection;

/**
 * Session cart mirrored to the `carts` table (keyed by user or a long-lived cookie) so it survives
 * the session and can drive reminders. Only ids and quantities are stored; prices are re-read.
 */
class Cart
{
    public const KEY = 'cart';

    public const COOKIE = 'cart_token';

    protected static ?Collection $items = null;

    public static function raw(): array
    {
        $cart = session(self::KEY);
        if ($cart === null) {
            // New session: bring back the cart saved for this user / browser.
            $cart = static::record()?->items ?? [];
            session([self::KEY => $cart]);
            if ($cart) {
                session()->now('cart_restored', true);
            }
        }

        return $cart;
    }

    protected static function record(): ?SavedCart
    {
        if ($id = auth()->id()) {
            return SavedCart::where('user_id', $id)->latest('updated_at')->first();
        }
        $token = request()->cookie(self::COOKIE);

        return $token ? SavedCart::where('token', $token)->whereNull('user_id')->first() : null;
    }

    /** After login: combine the guest cart with the one saved on the account. */
    public static function mergeOnLogin(): void
    {
        $guest = session(self::KEY) ?? [];
        $saved = SavedCart::where('user_id', auth()->id())->latest('updated_at')->first()?->items ?? [];
        if ($token = request()->cookie(self::COOKIE)) {
            SavedCart::where('token', $token)->whereNull('user_id')->delete();
        }
        static::save(array_merge($saved, $guest));
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
        $products = Product::active()->with(['options' => fn ($q) => $q->where('is_active', true)])
            ->whereIn('id', array_column($raw, 'product_id'))->get()->keyBy('id');

        return static::$items = collect($raw)->map(function ($row, $key) use ($products) {
            $product = $products[$row['product_id']] ?? null;
            if (! $product) {
                return null;
            }
            $selected = $product->options->whereIn('id', $row['options'])->values();
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

    /** "Standard Edition | Gift Wrap, Batteries" in the given locale; empty for products without options. */
    public static function optionsLabel(Collection $options, string $locale, Product $product): string
    {
        $name = fn ($o) => $o->{"name_{$locale}"} ?: $o->name_ar;
        $editions = $options->where('type', 'edition')->map($name);
        $addons = $options->where('type', 'addon')->map($name);
        if ($editions->isEmpty() && ($default = static::defaultEdition($product))) {
            $editions = collect([$name($default)]);
        }

        return collect([$editions->implode(', '), $addons->implode(', ')])->filter()->implode(' | ');
    }

    /** The first free edition is the pre-selected one and is not stored in the cart. */
    public static function defaultEdition(Product $product): ?ProductOption
    {
        return $product->options->where('type', 'edition')->where('is_active', true)->first(fn ($o) => (float) $o->price == 0);
    }

    protected static function validOptionIds(Product $product, array $ids): array
    {
        if (! $ids) {
            return [];
        }
        $options = $product->options()->where('is_active', true)->whereIn('id', $ids)->get();
        // Only one edition may be chosen; any number of add-ons.
        $edition = $options->where('type', 'edition')->first();

        return $options->where('type', 'addon')->pluck('id')
            ->when($edition && $edition->id !== static::defaultEdition($product)?->id, fn ($c) => $c->push($edition->id))
            ->map(fn ($id) => (int) $id)->values()->all();
    }

    protected static function save(array $cart): void
    {
        session([self::KEY => $cart]);
        static::$items = null;

        try {
            $token = request()->cookie(self::COOKIE) ?: (string) Str::uuid();
            Cookie::queue(self::COOKIE, $token, 60 * 24 * 365);

            $record = static::record() ?? new SavedCart(auth()->check() ? ['user_id' => auth()->id()] : ['token' => $token]);
            $record->fill([
                'items' => $cart,
                'items_count' => static::count(),
                'subtotal' => static::subtotal(),
                'reminded_at' => null,
            ])->save();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
