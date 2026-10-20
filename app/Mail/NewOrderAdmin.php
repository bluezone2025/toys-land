<?php

namespace App\Mail;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class NewOrderAdmin extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->locale('ar');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '🔔 طلب جديد #'.$this->order->number.' — '.number_format((float) $this->order->total, 3).' د.ك — '.$this->order->name);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.admin-order', with: [
            'order' => $this->order->load('items', 'country'),
            'adminUrl' => OrderResource::getUrl('view', ['record' => $this->order]),
            'invoiceUrl' => URL::signedRoute('invoice.show', $this->order),
        ]);
    }
}
