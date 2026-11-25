<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentCallbackStatus;
use App\Jobs\ProcessPaymentCallback;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\PaymentCallback;
use App\Notifications\PaymentSucceededNotification;
use App\Services\Payments\DuitkuException;
use App\Services\Payments\PaymentCallbackService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Notification::fake();
    config(['duitku.merchant_code' => 'DS12345', 'duitku.api_key' => 'kunci-rahasia', 'duitku.retry_sleep_ms' => 0]);
    $this->order = Order::factory()->create(['amount' => 150000, 'duitku_reference' => 'DSREF1']);
});

function callbackPayload(array $overrides = []): array
{
    $order = test()->order;
    $data = [
        'merchantCode' => 'DS12345',
        'amount' => '150000',
        'merchantOrderId' => $order->order_number,
        'productDetail' => 'Lele Galon',
        'paymentCode' => 'SP',
        'resultCode' => '00',
        'reference' => 'DSREF1',
        ...$overrides,
    ];
    $data['signature'] ??= md5($data['merchantCode'].$data['amount'].$data['merchantOrderId'].'kunci-rahasia');

    return $data;
}

function duitkuStatus(string $code, int $amount = 150000): void
{
    Http::fake(['*/api/merchant/transactionStatus' => Http::response(['statusCode' => $code, 'amount' => (string) $amount, 'reference' => 'DSREF1'])]);
}

function sendCallback(array $payload)
{
    return test()->post('/payments/duitku/callback', $payload);
}

it('rejects malformed callbacks with 400 and stores nothing', function (array $overrides) {
    sendCallback(callbackPayload($overrides))->assertStatus(400);

    expect(PaymentCallback::query()->count())->toBe(0);
})->with([
    'missing reference' => [['reference' => '']],
    'bad result code' => [['resultCode' => 'X']],
    'negative amount' => [['amount' => '-5']],
    'odd order number' => [['merchantOrderId' => 'abc']],
    'array amount' => [['amount' => ['1'], 'signature' => 'x']],
]);

it('rejects a different merchant with 403', function () {
    sendCallback(callbackPayload(['merchantCode' => 'OTHER', 'signature' => 'x']))->assertForbidden();

    expect(PaymentCallback::query()->count())->toBe(0);
});

it('rejects a wrong signature with 403 and records it as rejected', function () {
    sendCallback(callbackPayload(['signature' => md5('palsu')]))->assertForbidden();

    $callback = PaymentCallback::query()->sole();
    expect($callback)->status->toBe(PaymentCallbackStatus::Rejected)->signature_valid->toBeFalse()
        ->and($this->order->fresh()->status)->toBe(OrderStatus::Pending);
});

it('pays the order after Duitku confirms the status', function () {
    duitkuStatus('00');

    sendCallback(callbackPayload())->assertOk();

    expect($this->order->fresh())->status->toBe(OrderStatus::Paid)->duitku_payment_code->toBe('SP')
        ->and(Enrollment::query()->where('order_id', $this->order->id)->count())->toBe(1)
        ->and(PaymentCallback::query()->sole()->status)->toBe(PaymentCallbackStatus::Processed)
        ->and(PaymentCallback::query()->sole()->processed_at)->not->toBeNull();
    Notification::assertSentTo($this->order->user, PaymentSucceededNotification::class);
});

it('processes the same callback only once', function () {
    duitkuStatus('00');

    sendCallback(callbackPayload())->assertOk();
    sendCallback(callbackPayload())->assertOk();

    expect(PaymentCallback::query()->count())->toBe(1);
    Http::assertSentCount(1);
    Notification::assertSentToTimes($this->order->user, PaymentSucceededNotification::class, 1);
});

it('queues the processing job and answers right away', function () {
    Queue::fake();

    sendCallback(callbackPayload())->assertOk();

    Queue::assertPushed(ProcessPaymentCallback::class);
    expect($this->order->fresh()->status)->toBe(OrderStatus::Pending);
});

it('rejects a payment whose amount or reference does not match the order', function (array $overrides) {
    duitkuStatus('00');

    sendCallback(callbackPayload($overrides))->assertOk();

    expect(PaymentCallback::query()->sole()->status)->toBe(PaymentCallbackStatus::Rejected)
        ->and($this->order->fresh()->status)->toBe(OrderStatus::Pending);
})->with([
    'amount' => [['amount' => '100']],
    'reference' => [['reference' => 'DSLAIN']],
]);

it('rejects when Duitku says failed or reports another amount', function (string $code, int $amount) {
    duitkuStatus($code, $amount);

    sendCallback(callbackPayload())->assertOk();

    expect(PaymentCallback::query()->sole()->status)->toBe(PaymentCallbackStatus::Rejected)
        ->and($this->order->fresh()->status)->toBe(OrderStatus::Pending);
})->with([
    'failed status' => ['02', 150000],
    'other amount' => ['00', 99000],
]);

it('retries while Duitku is still processing or unreachable', function (callable $fake) {
    $fake();
    $callback = PaymentCallback::factory()->create([
        'order_id' => $this->order->id, 'merchant_order_id' => $this->order->order_number,
        'duitku_reference' => 'DSREF1', 'amount' => 150000, 'result_code' => '00',
    ]);

    expect(fn () => app(PaymentCallbackService::class)->process($callback))->toThrow(DuitkuException::class);
    expect($callback->fresh()->status)->toBe(PaymentCallbackStatus::Received)
        ->and($this->order->fresh()->status)->toBe(OrderStatus::Pending);
})->with([
    'pending at Duitku' => [fn () => duitkuStatus('01')],
    'connection down' => [fn () => Http::fake(['*' => Http::failedConnection()])],
]);

it('gives the job five tries with growing waits and marks the callback failed at the end', function () {
    $callback = PaymentCallback::factory()->create(['merchant_order_id' => $this->order->order_number]);
    $job = new ProcessPaymentCallback($callback->id, $callback->merchant_order_id);

    expect($job->tries)->toBe(5)->and($job->backoff())->toBe([10, 60, 300, 900, 1800]);

    $job->failed(new DuitkuException('Duitku tidak bisa dihubungi.'));
    expect($callback->fresh())->status->toBe(PaymentCallbackStatus::Failed)->last_error->toContain('Duitku tidak bisa dihubungi.');
});

it('ignores a failed-payment callback after the order is paid', function () {
    $this->order->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);

    sendCallback(callbackPayload(['resultCode' => '01']))->assertOk();

    expect($this->order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and(PaymentCallback::query()->sole()->status)->toBe(PaymentCallbackStatus::Ignored);
});

it('marks a pending order failed only when Duitku confirms the failure', function () {
    duitkuStatus('02');

    sendCallback(callbackPayload(['resultCode' => '01']))->assertOk();

    expect($this->order->fresh()->status)->toBe(OrderStatus::Failed)
        ->and(PaymentCallback::query()->sole()->status)->toBe(PaymentCallbackStatus::Processed);
});

it('does not trust an unsigned failure code that Duitku does not confirm', function (string $code, OrderStatus $expected) {
    duitkuStatus($code);

    sendCallback(callbackPayload(['resultCode' => '01']))->assertOk();

    expect($this->order->fresh()->status)->toBe($expected);
})->with([
    'still pending at Duitku' => ['01', OrderStatus::Pending],
    'actually paid at Duitku' => ['00', OrderStatus::Paid],
]);

it('still pays an expired or cancelled order when money arrives', function (OrderStatus $status) {
    $this->order->update(['status' => $status]);
    duitkuStatus('00');

    sendCallback(callbackPayload())->assertOk();

    expect($this->order->fresh()->status)->toBe(OrderStatus::Paid);
})->with([OrderStatus::Expired, OrderStatus::Cancelled]);

it('never pays a refunded order again', function () {
    $this->order->update(['status' => OrderStatus::Refunded]);
    duitkuStatus('00');

    sendCallback(callbackPayload())->assertOk();

    expect($this->order->fresh()->status)->toBe(OrderStatus::Refunded)
        ->and(PaymentCallback::query()->sole()->status)->toBe(PaymentCallbackStatus::Ignored);
    Http::assertNothingSent();
});

it('answers 200 and ignores an unknown order number', function () {
    sendCallback(callbackPayload(['merchantOrderId' => 'ZZZZZZZZZZZ']))->assertOk();

    expect(PaymentCallback::query()->sole()->status)->toBe(PaymentCallbackStatus::Ignored);
});

it('keeps signatures out of the payload and the log', function () {
    Log::spy();
    duitkuStatus('00');
    $payload = callbackPayload();

    sendCallback($payload);
    sendCallback(callbackPayload(['signature' => md5('palsu')]));

    foreach (PaymentCallback::all() as $callback) {
        expect($callback->payload)->not->toHaveKey('signature')
            ->and(json_encode($callback->payload))->not->toContain($payload['signature']);
    }
    foreach (['info', 'warning', 'error', 'log'] as $level) {
        Log::shouldNotHaveReceived($level, fn (...$arguments) => str_contains(json_encode($arguments), $payload['signature']) || str_contains(json_encode($arguments), 'kunci-rahasia'));
    }
});

it('accepts the callback without a CSRF token', function () {
    duitkuStatus('00');

    $this->withMiddleware()->post('/payments/duitku/callback', callbackPayload())->assertOk();
});

it('keeps the payment method from a callback that arrives after the popup already paid the order', function () {
    $this->order->forceFill(['status' => OrderStatus::Paid, 'paid_at' => now(), 'duitku_payment_code' => null])->save();

    sendCallback(callbackPayload())->assertOk();

    expect($this->order->fresh()->duitku_payment_code)->toBe('SP')
        ->and(PaymentCallback::query()->sole()->status)->toBe(PaymentCallbackStatus::Ignored);
});

it('never overwrites a payment method that is already known', function () {
    $this->order->forceFill(['status' => OrderStatus::Paid, 'paid_at' => now(), 'duitku_payment_code' => 'OV'])->save();

    sendCallback(callbackPayload())->assertOk();

    expect($this->order->fresh()->duitku_payment_code)->toBe('OV');
});
