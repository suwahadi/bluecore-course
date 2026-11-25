<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

it('sends the buyer back to their order without touching its status', function () {
    $order = Order::factory()->create();

    $this->actingAs($order->user)->get('/payments/duitku/return?merchantOrderId='.$order->order_number.'&resultCode=00&reference=DSREF')
        ->assertRedirect(route('order.show', $order));

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

it('sends other users and unknown numbers to their own order list', function (string $query) {
    $order = Order::factory()->create();

    $this->actingAs(User::factory()->create())->get('/payments/duitku/return?'.str_replace('{number}', $order->order_number, $query))
        ->assertRedirect(route('orders.index'));
})->with([
    'someone else order' => ['merchantOrderId={number}'],
    'unknown number' => ['merchantOrderId=ZZZZZZZZZZZ'],
    'missing number' => [''],
    'array number' => ['merchantOrderId[]=x'],
]);

it('asks guests to sign in on the same URL', function () {
    $this->get('/payments/duitku/return?merchantOrderId=ABCDEFGHIJK')->assertStatus(401);
});
