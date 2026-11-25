<?php

use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Services\Payments\DuitkuClient;
use App\Services\Payments\DuitkuException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config([
        'duitku.merchant_code' => 'DS12345',
        'duitku.api_key' => 'rahasia-api-key',
        'duitku.base_url' => 'https://api-sandbox.duitku.com',
        'duitku.retry_sleep_ms' => 0,
    ]);
});

function invoiceOrder(): Order
{
    $user = User::factory()->create(['name' => 'Sari Lestari', 'email' => 'sari@contoh.id', 'phone' => '08123456789']);
    $course = Course::factory()->published()->create(['title' => 'Lele Galon']);

    return Order::factory()->for($user)->for($course)->create(['subtotal' => 150000, 'admin_fee' => 2500, 'amount' => 152500]);
}

it('creates an invoice with the signed headers and the order data', function () {
    $this->travelTo(now()->setTimestamp(1_790_000_000));
    Http::fake(['api-sandbox.duitku.com/*' => Http::response(['reference' => 'DS1234525XYZ', 'paymentUrl' => 'https://app-sandbox.duitku.com/redirect_checkout?reference=DS1234525XYZ', 'statusCode' => '00'])]);
    $order = invoiceOrder();

    $result = app(DuitkuClient::class)->createInvoice($order);

    expect($result)->toBe(['reference' => 'DS1234525XYZ', 'payment_url' => 'https://app-sandbox.duitku.com/redirect_checkout?reference=DS1234525XYZ']);
    Http::assertSent(function (Request $request) use ($order) {
        $timestamp = '1790000000000';

        return $request->url() === 'https://api-sandbox.duitku.com/api/merchant/createInvoice'
            && $request->method() === 'POST'
            && $request->header('x-duitku-merchantcode')[0] === 'DS12345'
            && $request->header('x-duitku-timestamp')[0] === $timestamp
            && $request->header('x-duitku-signature')[0] === hash('sha256', 'DS12345'.$timestamp.'rahasia-api-key')
            && $request['paymentAmount'] === 152500
            && $request['merchantOrderId'] === $order->order_number
            && $request['email'] === 'sari@contoh.id'
            && $request['customerVaName'] === 'Sari Lestari'
            && $request['productDetails'] === 'Lele Galon'
            && $request['callbackUrl'] === url('/payments/duitku/callback')
            && $request['returnUrl'] === url('/payments/duitku/return')
            && $request['expiryPeriod'] === (int) config('duitku.expiry_minutes')
            && array_sum(array_map(fn ($item) => $item['price'] * $item['quantity'], $request['itemDetails'])) === 152500;
    });
});

it('throws when Duitku answers without a reference or with an error status', function (array|int $reply) {
    Http::fake(['api-sandbox.duitku.com/*' => is_int($reply) ? Http::response(['Message' => 'error'], $reply) : Http::response($reply)]);

    app(DuitkuClient::class)->createInvoice(invoiceOrder());
})->with([
    'no reference' => [['statusCode' => '00']],
    'server error' => [500],
    'bad request' => [400],
])->throws(DuitkuException::class);

it('retries a dropped connection twice, then gives up', function () {
    Http::fake(['api-sandbox.duitku.com/*' => Http::failedConnection()]);

    expect(fn () => app(DuitkuClient::class)->createInvoice(invoiceOrder()))->toThrow(DuitkuException::class);
    Http::assertSentCount(3);
});

it('checks the transaction status with an md5 signature', function () {
    Http::fake(['api-sandbox.duitku.com/*' => Http::response(['merchantOrderId' => 'ABCDEFGHIJK', 'reference' => 'DS1', 'amount' => '152500', 'statusCode' => '00', 'statusMessage' => 'SUCCESS'])]);

    $status = app(DuitkuClient::class)->transactionStatus('ABCDEFGHIJK');

    expect($status)->toBe(['status_code' => '00', 'amount' => 152500, 'reference' => 'DS1']);
    Http::assertSent(fn (Request $request) => $request->url() === 'https://api-sandbox.duitku.com/api/merchant/transactionStatus'
        && $request['merchantCode'] === 'DS12345'
        && $request['merchantOrderId'] === 'ABCDEFGHIJK'
        && $request['signature'] === md5('DS12345ABCDEFGHIJKrahasia-api-key'));
});

it('throws on an unknown status code', function () {
    Http::fake(['api-sandbox.duitku.com/*' => Http::response(['statusCode' => '99'])]);

    app(DuitkuClient::class)->transactionStatus('ABCDEFGHIJK');
})->throws(DuitkuException::class);

it('computes the callback signature', function () {
    expect(app(DuitkuClient::class)->callbackSignature('DS12345', 152500, 'ABCDEFGHIJK'))->toBe(md5('DS12345152500ABCDEFGHIJKrahasia-api-key'));
});

it('never writes the API key or a signature to the log', function () {
    Log::spy();
    Http::fake(['api-sandbox.duitku.com/*' => Http::response(['Message' => 'error'], 500)]);

    try {
        app(DuitkuClient::class)->createInvoice(invoiceOrder());
    } catch (DuitkuException) {
    }

    Log::shouldNotHaveReceived('info', fn () => true);
    foreach (['warning', 'error', 'debug', 'info'] as $level) {
        Log::shouldNotHaveReceived($level, function ($message, $context = []) {
            return str_contains($message.json_encode($context), 'rahasia-api-key') || str_contains($message.json_encode($context), 'x-duitku-signature');
        });
    }
});

it('names payment methods and keeps unknown codes as they are', function () {
    $client = app(DuitkuClient::class);

    expect($client->paymentName('SP'))->toBe('ShopeePay')
        ->and($client->paymentName('ZZ'))->toBe('ZZ')
        ->and($client->paymentName(null))->toBeNull();
});

it('refuses to call Duitku before the merchant code and API key are set', function (string $missing) {
    config(["duitku.{$missing}" => null]);
    Http::fake();

    expect(fn () => app(DuitkuClient::class)->transactionStatus('ABCDEFGHIJK'))->toThrow(DuitkuException::class, 'Duitku belum dikonfigurasi.');
    expect(fn () => app(DuitkuClient::class)->createInvoice(invoiceOrder()))->toThrow(DuitkuException::class, 'Duitku belum dikonfigurasi.');
    Http::assertNothingSent();
})->with(['merchant_code', 'api_key']);
