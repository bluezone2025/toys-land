<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\Product;
use App\Support\Cart;
use App\Support\Shop;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show()
    {
        $subtotal = Cart::subtotal();

        return view('store.cart', [
            'items' => Cart::items(),
            'count' => Cart::count(),
            'subtotal' => $subtotal,
            'shipping' => CheckoutController::shippingFor(Shop::country(), $subtotal),
        ]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:99'],
            'edition' => ['nullable', 'integer'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['integer'],
        ]);
        $product = Product::active()->findOrFail($data['product_id']);
        $qty = (int) ($data['qty'] ?? 1);

        if (! $product->inStock()) {
            return $this->respond($request, tt('هاللعبة خلصت من المخزون حالياً', 'This toy is currently out of stock'), false);
        }

        Cart::add($product, array_filter([$data['edition'] ?? null, ...($data['addons'] ?? [])]), $qty);
        AnalyticsEvent::record('add_to_cart', ['product_id' => $product->id, 'value' => $product->price * $qty]);

        $message = tt('انضافت ('.$product->name_ar.') بالسلة 🛒', 'Added ('.$product->name_en.') to Cart 🛒');

        return $request->expectsJson()
            ? response()->json(['count' => Cart::count(), 'message' => $message])
            : redirect()->route('cart')->with('toast', $message);
    }

    public function update(Request $request, string $key)
    {
        Cart::changeQty($key, $request->integer('delta') >= 0 ? 1 : -1);

        return redirect()->route('cart');
    }

    public function remove(string $key)
    {
        Cart::remove($key);

        return redirect()->route('cart')->with('toast', tt('تم حذف اللعبة من السلة', 'Item removed from cart'));
    }

    protected function respond(Request $request, string $message, bool $ok)
    {
        return $request->expectsJson()
            ? response()->json(['count' => Cart::count(), 'message' => $message], $ok ? 200 : 422)
            : back()->with('toast', $message);
    }
}
