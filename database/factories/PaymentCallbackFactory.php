<?php

namespace Database\Factories;

use App\Enums\PaymentCallbackStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentCallbackFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'merchant_order_id' => Str::upper(Str::random(11)),
            'duitku_reference' => 'DS'.fake()->numerify('############'),
            'result_code' => '00',
            'amount' => 150000,
            'event_key' => hash('sha256', Str::random(32)),
            'signature_valid' => true,
            'payload' => ['resultCode' => '00'],
            'status' => PaymentCallbackStatus::Received,
        ];
    }
}
