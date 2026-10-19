<?php

namespace App\Mail;

use App\Models\SavedCart;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CartReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SavedCart $cart) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '🧸 ألعابك لسه تنتظرك في السلة | Your toys are waiting');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.cart-reminder', with: ['cart' => $this->cart, 'lines' => $this->cart->lines()]);
    }
}
