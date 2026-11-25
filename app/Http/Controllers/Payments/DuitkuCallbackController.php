<?php

namespace App\Http\Controllers\Payments;

use App\Enums\PaymentCallbackStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessPaymentCallback;
use App\Models\Order;
use App\Models\PaymentCallback;
use App\Services\Payments\DuitkuClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DuitkuCallbackController extends Controller
{
    public function __invoke(Request $request, DuitkuClient $duitku): Response
    {
        $validator = Validator::make($request->all(), [
            'merchantCode' => ['required', 'string', 'max:50'],
            'merchantOrderId' => ['required', 'string', 'regex:/^[A-Z0-9]{11}$/'],
            'amount' => ['required', 'string', 'regex:/^[1-9][0-9]{0,11}$/'],
            'resultCode' => ['required', 'string', 'regex:/^[0-9]{2}$/'],
            'reference' => ['required', 'string', 'max:100'],
            'signature' => ['required', 'string', 'max:128'],
            'paymentCode' => ['nullable', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            Log::warning('Duitku callback with a bad shape', ['ip' => $request->ip(), 'fields' => array_keys($validator->errors()->toArray())]);

            return response('Bad Request', 400);
        }

        $data = $validator->validated();

        if (! hash_equals((string) config('duitku.merchant_code'), $data['merchantCode'])) {
            Log::warning('Duitku callback for another merchant', ['ip' => $request->ip()]);

            return response('Forbidden', 403);
        }

        $amount = (int) $data['amount'];
        $attributes = [
            'order_id' => Order::query()->where('order_number', $data['merchantOrderId'])->value('id'),
            'merchant_order_id' => $data['merchantOrderId'],
            'duitku_reference' => $data['reference'],
            'result_code' => $data['resultCode'],
            'amount' => $amount,
            'payload' => collect($request->except('signature'))->filter(fn ($value) => is_scalar($value) || $value === null)->all(),
        ];

        $expected = $duitku->callbackSignature($data['merchantCode'], $amount, $data['merchantOrderId']);

        if (! hash_equals($expected, strtolower($data['signature']))) {
            $callback = PaymentCallback::query()->create([...$attributes, 'signature_valid' => false, 'status' => PaymentCallbackStatus::Rejected, 'processed_at' => now()]);
            Log::warning('Duitku callback with a bad signature', ['callback_id' => $callback->id, 'order_number' => $data['merchantOrderId'], 'ip' => $request->ip()]);

            return response('Forbidden', 403);
        }

        $eventKey = hash('sha256', $data['merchantOrderId'].'|'.$data['reference'].'|'.$data['resultCode']);
        $callback = PaymentCallback::query()->createOrFirst(['event_key' => $eventKey], [...$attributes, 'signature_valid' => true]);

        Log::info('Duitku callback received', [
            'callback_id' => $callback->id,
            'order_number' => $callback->merchant_order_id,
            'reference' => $callback->duitku_reference,
            'result_code' => $callback->result_code,
            'status' => $callback->status->value,
            'ip' => $request->ip(),
        ]);

        if (! $callback->status->isFinal()) {
            ProcessPaymentCallback::dispatch($callback->id, $callback->merchant_order_id);
        }

        return response('OK', 200);
    }
}
