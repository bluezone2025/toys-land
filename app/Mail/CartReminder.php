<?php

namespace App\Mail;

use App\Models\Coupon;
use App\Models\SavedCart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class CartReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public SavedCart $cart)
    {
        $this->locale($cart->user?->locale === 'en' ? 'en' : 'ar');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->locale === 'en'
            ? '🧸 Your toys are still waiting in your cart'
            : '🧸 ألعابك لسه تنتظرك في السلة!');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.cart-reminder', with: [
            'cart' => $this->cart,
            'lines' => $this->cart->lines(),
            'coupon' => Coupon::promo(),
            'unsubscribeUrl' => URL::signedRoute('unsubscribe', ['type' => 'user', 'id' => $this->cart->user_id]),
        ]);
    }
}
