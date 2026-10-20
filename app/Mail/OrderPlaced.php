<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/** Thank-you email with the full invoice, in the language the customer ordered in. */
class OrderPlaced extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->locale($order->locale === 'en' ? 'en' : 'ar');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->locale === 'en'
            ? 'Your Toys Land order #'.$this->order->number.' — Thank you!'
            : 'شكراً لطلبك من تويز لاند 🎉 فاتورة الطلب #'.$this->order->number);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.order', with: [
            'order' => $this->order->load('items', 'country'),
            'invoiceUrl' => URL::signedRoute('invoice.show', $this->order),
            'trackUrl' => route('tracking', ['order' => $this->order->number, 'phone' => $this->order->phone]),
        ]);
    }
}
