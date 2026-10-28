<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal MyFatoorah v2 client: create a payment link and verify it on callback.
 */
class MyFatoorah
{
    /** Map our method keys to MyFatoorah PaymentMethodId values (KNET=1, VISA/MASTER=2, Apple Pay=11). */
    protected const METHOD_IDS = ['knet' => 1, 'card' => 2, 'applepay' => 11];

    protected function request(string $endpoint, array $payload): array
    {
        $response = Http::withToken(config('services.myfatoorah.key'))
            ->acceptJson()
            ->timeout(30)
            ->post(rtrim(config('services.myfatoorah.base_url'), '/').'/v2/'.$endpoint, $payload);

        $json = $response->json() ?? [];
        if (! $response->successful() || ! ($json['IsSuccess'] ?? false)) {
            throw new RuntimeException($json['Message'] ?? 'MyFatoorah request failed');
        }

        return $json['Data'];
    }

    public function enabled(): bool
    {
        return filled(config('services.myfatoorah.key'));
    }

    /** Returns the payment page URL for the order. */
    public function createPayment(Order $order): string
    {
        $data = $this->request('ExecutePayment', [
            'PaymentMethodId' => self::METHOD_IDS[$order->payment_method] ?? 1,
            'InvoiceValue' => (float) $order->total,
            'DisplayCurrencyIso' => 'KWD',
            'CustomerName' => $order->name,
            'CustomerEmail' => $order->email ?: null,
            'CustomerMobile' => substr(preg_replace('/\D/', '', $order->phone), -8),
            'Language' => $order->locale === 'en' ? 'en' : 'ar',
            'CustomerReference' => $order->number,
            'CallBackUrl' => route('payment.callback'),
            'ErrorUrl' => route('payment.callback'),
        ]);

        $order->update(['payment_invoice_id' => $data['InvoiceId'] ?? null]);

        return $data['PaymentURL'];
    }

    /** @return array{order_number: ?string, paid: bool, payment_id: string} */
    public function status(string $key, string $keyType = 'PaymentId'): array
    {
        $data = $this->request('GetPaymentStatus', ['Key' => $key, 'KeyType' => $keyType]);
        // MyFatoorah spells the successful transaction status "Succss".
        $paid = collect($data['InvoiceTransactions'] ?? [])->firstWhere('TransactionStatus', 'Succss')
            ?? collect($data['InvoiceTransactions'] ?? [])->firstWhere('TransactionStatus', 'Success');

        return [
            'order_number' => $data['CustomerReference'] ?? null,
            'paid' => ($data['InvoiceStatus'] ?? '') === 'Paid',
            'payment_id' => $keyType === 'PaymentId' ? $key : (string) ($paid['PaymentId'] ?? $key),
        ];
    }
}
