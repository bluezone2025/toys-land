<?php

namespace App\Support;

use App\Models\Favorite;

/**
 * Wishlist stored in the database for signed-in customers and in the session for guests.
 */
class Favorites
{
    protected static ?array $ids = null;

    public static function ids(): array
    {
        if (static::$ids !== null) {
            return static::$ids;
        }

        return static::$ids = auth()->check()
            ? Favorite::where('user_id', auth()->id())->pluck('product_id')->all()
            : session('favorites', []);
    }

    public static function has(int $productId): bool
    {
        return in_array($productId, static::ids());
    }

    /** @return bool true when the product is now a favorite */
    public static function toggle(int $productId): bool
    {
        $active = ! static::has($productId);

        if (auth()->check()) {
            $active
                ? Favorite::firstOrCreate(['user_id' => auth()->id(), 'product_id' => $productId])
                : Favorite::where('user_id', auth()->id())->where('product_id', $productId)->delete();
        } else {
            $ids = array_values(array_diff(session('favorites', []), [$productId]));
            if ($active) {
                $ids[] = $productId;
            }
            session(['favorites' => $ids]);
        }
        static::$ids = null;

        return $active;
    }

    public static function clear(): void
    {
        auth()->check()
            ? Favorite::where('user_id', auth()->id())->delete()
            : session()->forget('favorites');
        static::$ids = null;
    }

    /** Move guest favorites into the account after login/registration. */
    public static function mergeGuest(): void
    {
        foreach (session('favorites', []) as $id) {
            Favorite::firstOrCreate(['user_id' => auth()->id(), 'product_id' => $id]);
        }
        session()->forget('favorites');
        static::$ids = null;
    }
}
