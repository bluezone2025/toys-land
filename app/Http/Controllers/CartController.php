<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Cart;
use App\Support\Shop;
use App\Support\Tracking;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show()
    {
        $subtotal = Cart::subtotal();
        if (Cart::count() > 0) {
            Tracking::push(Tracking::track('view_cart', Tracking::cart(), ['value_kwd' => $subtotal, 'cart' => true]));
        }

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

        $key = Cart::add($product, array_filter([$data['edition'] ?? null, ...($data['addons'] ?? [])]), $qty);
        // The browser sends the event_id it will use for its own pixels, so both copies match.
        $event = $this->trackLine('add_to_cart', $key, $qty, $request->header('X-Event-Id'));

        $message = tt('انضافت ('.$product->name_ar.') بالسلة 🛒', 'Added ('.$product->name_en.') to Cart 🛒');

        if ($request->expectsJson()) {
            return response()->json(['count' => Cart::count(), 'message' => $message, 'tracking' => $event]);
        }
        Tracking::flash($event);

        return redirect()->route('cart')->with('toast', $message);
    }

    public function update(Request $request, string $key)
    {
        $delta = $request->integer('delta') >= 0 ? 1 : -1;
        $line = Cart::items()->firstWhere('key', $key);
        Cart::changeQty($key, $delta);
        if ($line) {
            Tracking::flash($delta > 0 ? $this->trackLine('add_to_cart', $key, 1) : $this->trackLine('remove_from_cart', $key, 1, line: $line));
        }

        return redirect()->route('cart');
    }

    public function remove(string $key)
    {
        if ($line = Cart::items()->firstWhere('key', $key)) {
            Cart::remove($key);
            Tracking::flash($this->trackLine('remove_from_cart', $key, $line->qty, line: $line));
        }

        return redirect()->route('cart')->with('toast', tt('تم حذف اللعبة من السلة', 'Item removed from cart'));
    }

    /** Track a cart change for one line (looked up after adding, or passed in before removal). */
    protected function trackLine(string $event, string $key, int $qty, ?string $eventId = null, ?object $line = null): ?array
    {
        $line ??= Cart::items()->firstWhere('key', $key);
        if (! $line) {
            return null;
        }
        $variant = Cart::optionsLabel($line->options, app()->getLocale(), $line->product);
        $item = Tracking::item($line->product, $qty, $line->unit_price, $variant !== '' ? ['item_variant' => $variant] : []);

        return Tracking::track($event, ['value' => round($item['price'] * $qty, 3), 'items' => [$item], 'cart_value' => Tracking::convert(Cart::subtotal())], [
            'event_id' => $eventId, 'product_id' => $line->product->id, 'value_kwd' => round($line->unit_price * $qty, 3), 'cart' => true,
        ]);
    }

    protected function respond(Request $request, string $message, bool $ok)
    {
        return $request->expectsJson()
            ? response()->json(['count' => Cart::count(), 'message' => $message], $ok ? 200 : 422)
            : back()->with('toast', $message);
    }
}
