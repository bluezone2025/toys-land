<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * WhatsApp Business Cloud API (Meta). Business-initiated messages must use a template
 * approved in Meta Business Manager; its body variables are filled in order.
 */
class WhatsApp
{
    public static function enabled(): bool
    {
        return Setting::get('whatsapp_api_enabled', '0') === '1'
            && filled(config('services.whatsapp.token')) && filled(config('services.whatsapp.phone_id'));
    }

    /** Kuwait mobiles are often typed as 8 digits; prefix the country code. */
    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        $digits = ltrim($digits, '0');
        if (strlen($digits) === 8) {
            $digits = '965'.$digits;
        }

        return strlen($digits) >= 10 ? $digits : null;
    }

    /** @param  array<int, string>  $params */
    public static function sendTemplate(string $phone, string $template, array $params, string $language = 'ar'): void
    {
        $response = Http::withToken(config('services.whatsapp.token'))->timeout(20)
            ->post('https://graph.facebook.com/v21.0/'.config('services.whatsapp.phone_id').'/messages', [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => $language],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => array_map(fn ($p) => ['type' => 'text', 'text' => (string) $p], $params),
                    ]],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('WhatsApp: '.($response->json('error.message') ?? $response->status()));
        }
    }
}
