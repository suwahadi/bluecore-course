<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\OrderExpiredNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Notification;

it('expires only pending orders past their deadline and emails each buyer once', function () {
    Notification::fake();
    $late = Order::factory()->create(['expires_at' => now()->subMinute()]);
    $onTime = Order::factory()->create(['expires_at' => now()->addHour()]);
    $paid = Order::factory()->paid()->create(['expires_at' => now()->subDay()]);

    $this->artisan('orders:expire')->assertSuccessful();
    $this->artisan('orders:expire')->assertSuccessful();

    expect($late->fresh()->status)->toBe(OrderStatus::Expired)
        ->and($onTime->fresh()->status)->toBe(OrderStatus::Pending)
        ->and($paid->fresh()->status)->toBe(OrderStatus::Paid);
    Notification::assertSentToTimes($late->user, OrderExpiredNotification::class, 1);
    Notification::assertNotSentTo($onTime->user, OrderExpiredNotification::class);
});

it('schedules expiry every ten minutes and the queue worker every minute', function () {
    $events = collect(app(Schedule::class)->events());

    $expire = $events->first(fn ($event) => str_contains($event->command ?? '', 'orders:expire'));
    $worker = $events->first(fn ($event) => str_contains($event->command ?? '', 'queue:work'));

    expect($expire?->expression)->toBe('*/10 * * * *')
        ->and($worker?->expression)->toBe('* * * * *')
        ->and($worker->command)->toContain('--stop-when-empty')->toContain('--max-time=50')
        ->and($worker->withoutOverlapping)->toBeTrue();
});
