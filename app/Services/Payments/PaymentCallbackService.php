<?php

namespace App\Services\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentCallbackStatus;
use App\Models\Order;
use App\Models\PaymentCallback;
use App\Services\Orders\OrderService;
use Illuminate\Support\Facades\Log;

class PaymentCallbackService
{
    public function __construct(private OrderService $orders, private DuitkuClient $duitku)
    {
    }

    public function process(PaymentCallback $callback): void
    {
        if ($callback->status->isFinal()) {
            return;
        }

        $order = Order::query()->where('order_number', $callback->merchant_order_id)->first();

        if ($order === null) {
            $this->finish($callback, PaymentCallbackStatus::Ignored, 'warning', 'Duitku callback for unknown order');

            return;
        }

        if ($callback->order_id === null) {
            $callback->forceFill(['order_id' => $order->id])->save();
        }

        if ($order->duitku_reference !== null && $callback->duitku_reference !== $order->duitku_reference) {
            $this->finish($callback, PaymentCallbackStatus::Rejected, 'error', 'Duitku callback reference mismatch');

            return;
        }

        match ($callback->result_code) {
            '00' => $this->processSuccess($callback, $order),
            '01' => $this->processFailure($callback, $order),
            default => $this->finish($callback, PaymentCallbackStatus::Ignored, 'info', 'Duitku callback with an unhandled result code'),
        };
    }

    public function applyConfirmedPayment(Order $order, array $status, ?string $paymentCode): bool
    {
        if (($status['status_code'] ?? null) !== '00') {
            return false;
        }

        if ((int) ($status['amount'] ?? 0) !== $order->amount) {
            Log::warning('Duitku status amount mismatch', ['order_number' => $order->order_number]);

            return false;
        }

        $reference = $status['reference'] ?? null;

        if ($order->duitku_reference !== null && $reference !== null && $reference !== $order->duitku_reference) {
            Log::warning('Duitku status reference mismatch', ['order_number' => $order->order_number, 'reference' => $reference]);

            return false;
        }

        return $this->orders->markPaid($order, $paymentCode, $reference ?? $order->duitku_reference);
    }

    private function processSuccess(PaymentCallback $callback, Order $order): void
    {
        if (in_array($order->status, [OrderStatus::Paid, OrderStatus::Refunded], true)) {
            $paymentCode = $callback->payload['paymentCode'] ?? null;

            if ($order->duitku_payment_code === null && filled($paymentCode)) {
                $order->forceFill(['duitku_payment_code' => $paymentCode])->save();
            }

            $this->finish($callback, PaymentCallbackStatus::Ignored);

            return;
        }

        if ($callback->amount !== $order->amount) {
            $this->finish($callback, PaymentCallbackStatus::Rejected, 'error', 'Duitku callback amount mismatch');

            return;
        }

        $status = $this->duitku->transactionStatus($order->order_number);

        if ($status['status_code'] === '01') {
            throw new DuitkuException('Duitku masih memproses pembayaran.');
        }

        if ($status['status_code'] !== '00' || $status['amount'] !== $order->amount) {
            $this->finish($callback, PaymentCallbackStatus::Rejected, 'warning', 'Duitku status did not confirm the callback');

            return;
        }

        $paid = $this->orders->markPaid($order, $callback->payload['paymentCode'] ?? null, $callback->duitku_reference);

        $this->finish($callback, $paid ? PaymentCallbackStatus::Processed : PaymentCallbackStatus::Ignored);
    }

    private function processFailure(PaymentCallback $callback, Order $order): void
    {
        if ($order->status !== OrderStatus::Pending) {
            $this->finish($callback, PaymentCallbackStatus::Ignored);

            return;
        }

        $status = $this->duitku->transactionStatus($order->order_number);

        if ($status['status_code'] === '00') {
            $paid = $this->applyConfirmedPayment($order, $status, $callback->payload['paymentCode'] ?? null);
            $this->finish($callback, $paid ? PaymentCallbackStatus::Processed : PaymentCallbackStatus::Ignored);

            return;
        }

        if ($status['status_code'] === '02') {
            $this->finish($callback, $this->orders->fail($order) ? PaymentCallbackStatus::Processed : PaymentCallbackStatus::Ignored);

            return;
        }

        $this->finish($callback, PaymentCallbackStatus::Ignored, 'warning', 'Duitku did not confirm the failure callback');
    }

    private function finish(PaymentCallback $callback, PaymentCallbackStatus $status, string $level = 'info', string $message = 'Duitku callback processed'): void
    {
        $callback->forceFill(['status' => $status, 'processed_at' => now(), 'last_error' => null])->save();

        Log::log($level, $message, [
            'callback_id' => $callback->id,
            'order_number' => $callback->merchant_order_id,
            'reference' => $callback->duitku_reference,
            'result_code' => $callback->result_code,
            'status' => $status->value,
        ]);
    }
}
