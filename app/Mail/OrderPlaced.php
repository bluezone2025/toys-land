<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlaced extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public bool $forAdmin = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->forAdmin
            ? 'طلب جديد #'.$this->order->number.' - '.number_format((float) $this->order->total, 3).' KWD'
            : 'تأكيد طلبك #'.$this->order->number.' | Order Confirmation');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.order', with: ['order' => $this->order->load('items')]);
    }
}
