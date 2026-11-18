<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentCallbackStatus;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\PaymentCallback;
use App\Models\SegmentProgress;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

it('generates order numbers and v4 uuids', function () {
    $order = Order::factory()->create();

    expect($order->order_number)->toMatch('/^[A-Z0-9]{11}$/')
        ->and($order->uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/')
        ->and($order->getRouteKeyName())->toBe('uuid')
        ->and($order->status)->toBe(OrderStatus::Pending);
});

it('creates unique order numbers', function () {
    $numbers = collect(range(1, 30))->map(fn () => Order::newOrderNumber());

    expect($numbers->unique()->count())->toBe(30);
});

it('filters orders by Jakarta dates inclusively', function () {
    $inside = Order::factory()->create(['created_at' => Carbon::parse('2026-10-31 23:59:00', 'Asia/Jakarta')]);
    Order::factory()->create(['created_at' => Carbon::parse('2026-10-30 23:59:59', 'Asia/Jakarta')]);
    Order::factory()->create(['created_at' => Carbon::parse('2026-11-01 00:00:00', 'Asia/Jakarta')]);

    expect(Order::createdBetween('2026-10-31', '2026-10-31')->pluck('id')->all())->toBe([$inside->id]);
});

it('treats empty date bounds as open', function () {
    Order::factory()->count(2)->create();

    expect(Order::createdBetween(null, null)->count())->toBe(2);
});

it('refuses to delete a course that has orders', function () {
    $order = Order::factory()->create();

    expect(fn () => $order->course->delete())->toThrow(QueryException::class);
});

it('allows one enrollment per user and course', function () {
    $enrollment = Enrollment::factory()->create();

    expect(fn () => Enrollment::factory()->create([
        'user_id' => $enrollment->user_id,
        'course_id' => $enrollment->course_id,
    ]))->toThrow(QueryException::class);
});

it('stores callback payloads as arrays with a status', function () {
    $callback = PaymentCallback::factory()->create(['payload' => ['resultCode' => '00']]);

    expect($callback->fresh()->payload)->toBe(['resultCode' => '00'])
        ->and($callback->fresh()->status)->toBe(PaymentCallbackStatus::Received);
});

it('keeps one progress row per user and segment', function () {
    $progress = SegmentProgress::factory()->create();

    expect(fn () => SegmentProgress::factory()->create([
        'user_id' => $progress->user_id,
        'course_segment_id' => $progress->course_segment_id,
    ]))->toThrow(QueryException::class);
});
