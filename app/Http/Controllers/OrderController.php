<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Tracking;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public static function canView(Order $order): bool
    {
        $user = auth()->user();

        return ($user && ($user->is_admin || $order->user_id === $user->id))
            || in_array($order->number, session('orders', []), true);
    }

    public function invoice(Order $order)
    {
        // Signed links (sent by email) open the invoice without a session.
        abort_unless(static::canView($order) || request()->hasValidSignature(), 403);
        // First view by the buyer: browser purchase with the same event_id as the server copy.
        Tracking::push(Tracking::browserPurchase($order));

        return view('store.invoice', ['order' => $order->load('items', 'country')]);
    }

    public function latest()
    {
        $number = auth()->check()
            ? auth()->user()->orders()->latest('id')->value('number')
            : (last(session('orders', [])) ?: null);

        return $number ? redirect()->route('invoice.show', $number) : redirect()->route('tracking');
    }

    public function tracking(Request $request)
    {
        $order = null;
        $notFound = false;

        if ($number = strtoupper(trim((string) $request->query('order')))) {
            $order = Order::where('number', $number)->first();
            $digits = fn ($v) => substr(preg_replace('/\D/', '', (string) $v), -8);
            $phoneMatches = $order && $digits($request->query('phone')) !== '' && $digits($request->query('phone')) === $digits($order->phone);

            if (! $order || (! static::canView($order) && ! $phoneMatches)) {
                $order = null;
                $notFound = $request->filled('phone');
            }
        }

        return view('store.tracking', compact('order', 'notFound'));
    }
}
