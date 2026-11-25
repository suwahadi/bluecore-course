<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Services\Orders\OrderService;
use Illuminate\Support\Facades\Log;

class PaymentCallbackService
{
    public function __construct(private OrderService $orders)
    {
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
}
