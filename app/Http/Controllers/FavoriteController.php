<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Favorites;
use App\Support\Tracking;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function toggle(Request $request)
    {
        $product = Product::active()->findOrFail($request->integer('product_id'));
        $active = Favorites::toggle($product->id);
        $event = null;
        if ($active) {
            $item = Tracking::item($product);
            $event = Tracking::track('add_to_wishlist', ['value' => $item['price'], 'items' => [$item]], [
                'event_id' => $request->header('X-Event-Id'), 'product_id' => $product->id, 'value_kwd' => (float) $product->price,
            ]);
        }

        return response()->json([
            'tracking' => $event,
            'active' => $active,
            'count' => count(Favorites::ids()),
            'message' => $active
                ? tt('ضفنا اللعبة للمفضلة بنجاح ❤️', 'Added to Wishlist ❤️')
                : tt('شلنا اللعبة من المفضلة 🤍', 'Removed from Wishlist 🤍'),
        ]);
    }

    public function clear()
    {
        Favorites::clear();

        return back()->with('toast', tt('تم إفراغ قائمة المفضلة بالكامل', 'Wishlist cleared'));
    }
}
