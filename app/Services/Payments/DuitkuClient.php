<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DuitkuClient
{
    private const STATUS_CODES = ['00', '01', '02'];

    public function createInvoice(Order $order): array
    {
        $order->loadMissing(['user:id,name,email,phone', 'course:id,title']);
        $merchantCode = (string) config('duitku.merchant_code');
        $timestamp = (string) now()->getTimestampMs();
        $title = Str::limit($order->course->title, 250, '');

        $response = $this->post(config('duitku.paths.create_invoice'), [
            'paymentAmount' => $order->amount,
            'merchantOrderId' => $order->order_number,
            'productDetails' => $title,
            'email' => $order->user->email,
            'phoneNumber' => $order->user->phone,
            'customerVaName' => Str::limit($order->user->name, 50, ''),
            'callbackUrl' => url('/payments/duitku/callback'),
            'returnUrl' => url('/payments/duitku/return'),
            'expiryPeriod' => (int) config('duitku.expiry_minutes'),
            'itemDetails' => array_values(array_filter([
                ['name' => $title, 'price' => $order->subtotal, 'quantity' => 1],
                $order->admin_fee > 0 ? ['name' => 'Biaya admin', 'price' => $order->admin_fee, 'quantity' => 1] : null,
            ])),
        ], [
            'x-duitku-merchantcode' => $merchantCode,
            'x-duitku-timestamp' => $timestamp,
            'x-duitku-signature' => hash('sha256', $merchantCode.$timestamp.config('duitku.api_key')),
        ]);

        $reference = $response['reference'] ?? null;
        $paymentUrl = $response['paymentUrl'] ?? null;

        if (! is_string($reference) || $reference === '' || ! is_string($paymentUrl) || $paymentUrl === '') {
            throw new DuitkuException('Duitku tidak mengembalikan referensi pembayaran.');
        }

        return ['reference' => $reference, 'payment_url' => $paymentUrl];
    }

    public function transactionStatus(string $orderNumber): array
    {
        $merchantCode = (string) config('duitku.merchant_code');

        $response = $this->post(config('duitku.paths.transaction_status'), [
            'merchantCode' => $merchantCode,
            'merchantOrderId' => $orderNumber,
            'signature' => md5($merchantCode.$orderNumber.config('duitku.api_key')),
        ]);

        $code = $response['statusCode'] ?? null;

        if (! in_array($code, self::STATUS_CODES, true)) {
            throw new DuitkuException('Duitku mengembalikan kode status yang tidak dikenal.');
        }

        return [
            'status_code' => $code,
            'amount' => (int) ($response['amount'] ?? 0),
            'reference' => is_string($response['reference'] ?? null) ? $response['reference'] : null,
        ];
    }

    public function callbackSignature(string $merchantCode, int $amount, string $orderNumber): string
    {
        return md5($merchantCode.$amount.$orderNumber.config('duitku.api_key'));
    }

    public function paymentName(?string $code): ?string
    {
        return $code === null ? null : (config('duitku.payment_names')[$code] ?? $code);
    }

    private function post(string $path, array $body, array $headers = []): array
    {
        try {
            $response = Http::baseUrl((string) config('duitku.base_url'))
                ->timeout((int) config('duitku.timeout'))
                ->retry((int) config('duitku.retries') + 1, (int) config('duitku.retry_sleep_ms'), fn ($exception) => $exception instanceof ConnectionException, throw: false)
                ->acceptJson()
                ->asJson()
                ->withHeaders($headers)
                ->post($path, $body);
        } catch (ConnectionException $exception) {
            throw new DuitkuException('Duitku tidak bisa dihubungi.', previous: $exception);
        }

        $data = $response->json();

        if ($response->failed() || ! is_array($data)) {
            throw new DuitkuException('Duitku menolak permintaan (HTTP '.$response->status().').');
        }

        return $data;
    }
}
