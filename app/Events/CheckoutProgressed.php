<?php

namespace App\Events;

use App\Models\AbandonedCheckout;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A shopper left a phone number or email on the checkout page. Listeners can start
 * WhatsApp / SMS recovery flows; `checkout:recover` handles the built-in WhatsApp one.
 */
class CheckoutProgressed
{
    use Dispatchable;

    public function __construct(public AbandonedCheckout $checkout) {}
}
