<?php

namespace App\Mail;

use App\Models\Coupon;
use App\Models\EmailCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/** One campaign email to one recipient (registered customer or guest buyer). */
class CampaignMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public EmailCampaign $campaign, public string $name, public string $type, public int $recipientId, string $locale = 'ar')
    {
        $this->locale($locale === 'en' ? 'en' : 'ar');
        $this->onQueue('newsletter');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->locale === 'en' ? ($this->campaign->subject_en ?: $this->campaign->subject_ar) : $this->campaign->subject_ar);
    }

    public function content(): Content
    {
        $coupon = $this->campaign->coupon ? Coupon::findValid($this->campaign->coupon->code, PHP_FLOAT_MAX) : null;

        return new Content(view: 'mail.campaign', with: [
            'campaign' => $this->campaign,
            'name' => $this->name,
            'coupon' => $coupon,
            'unsubscribeUrl' => URL::signedRoute('unsubscribe', ['type' => $this->type, 'id' => $this->recipientId]),
        ]);
    }
}
