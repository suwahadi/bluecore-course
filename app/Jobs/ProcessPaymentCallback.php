<?php

namespace App\Jobs;

use App\Enums\PaymentCallbackStatus;
use App\Models\PaymentCallback;
use App\Services\Payments\PaymentCallbackService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProcessPaymentCallback implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public int $callbackId, public string $orderNumber)
    {
    }

    public function backoff(): array
    {
        return [10, 60, 300, 900, 1800];
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('duitku-order-'.$this->orderNumber))->releaseAfter(10)->expireAfter(120)];
    }

    public function handle(PaymentCallbackService $service): void
    {
        $callback = PaymentCallback::query()->find($this->callbackId);

        if ($callback === null) {
            return;
        }

        $callback->increment('attempts');
        $service->process($callback);
    }

    public function failed(Throwable $exception): void
    {
        PaymentCallback::query()->whereKey($this->callbackId)->update([
            'status' => PaymentCallbackStatus::Failed,
            'last_error' => Str::limit(class_basename($exception).': '.$exception->getMessage(), 250, ''),
        ]);

        Log::error('Duitku callback failed for good', ['callback_id' => $this->callbackId, 'order_number' => $this->orderNumber]);
    }
}
