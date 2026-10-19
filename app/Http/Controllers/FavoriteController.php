<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\Product;
use App\Support\Favorites;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function toggle(Request $request)
    {
        $product = Product::active()->findOrFail($request->integer('product_id'));
        $active = Favorites::toggle($product->id);
        if ($active) {
            AnalyticsEvent::record('wishlist', ['product_id' => $product->id]);
        }

        return response()->json([
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
