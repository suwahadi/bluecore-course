<?php

use App\Enums\OrderStatus;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;

it('shows revenue, today orders, published courses and users', function () {
    $admin = User::factory()->admin()->create();
    $buyer = User::factory()->create();
    $waiting = User::factory()->create(['name' => 'Rina Waiting']);
    $refunded = User::factory()->create();
    [$first, $second] = Course::factory()->published()->count(2)->create();
    Course::factory()->create();

    Order::factory()->paid()->for($buyer)->for($first)->create(['amount' => 150000]);
    Order::factory()->paid()->for($buyer)->for($second)->create([
        'amount' => 100000, 'paid_at' => now()->subMonthNoOverflow(), 'created_at' => now()->subMonthNoOverflow(),
    ]);
    $pending = Order::factory()->for($waiting)->for($first)->create(['amount' => 150000]);
    Order::factory()->for($refunded)->for($first)->create(['status' => OrderStatus::Refunded, 'amount' => 80000]);

    $this->actingAs($admin)->get('/admin')
        ->assertOk()
        ->assertSeeText('Rp 250.000')
        ->assertSeeText('Dari 2 pesanan lunas')
        ->assertSeeText('Bulan ini')
        ->assertSeeText('Rp 150.000')
        ->assertSeeText('Rata-rata per pesanan')
        ->assertSeeText('Rp 125.000')
        ->assertSeeText('1 menunggu pembayaran')
        ->assertSeeText('1 masih draf')
        ->assertSeeText('1 sudah membeli kursus')
        ->assertSeeText('Pesanan menunggu pembayaran')
        ->assertSeeText($pending->order_number)
        ->assertSeeText('Rina Waiting');
});

it('says when nothing is waiting and nothing is a draft', function () {
    Course::factory()->published()->create();

    $this->actingAs(User::factory()->admin()->create())->get('/admin')
        ->assertSeeText('Rp 0')
        ->assertSeeText('Tidak ada yang menunggu pembayaran')
        ->assertSeeText('Tidak ada draf')
        ->assertSeeText('Tidak ada pesanan yang menunggu pembayaran.');
});

it('counts today orders in the business day', function () {
    $this->travelTo(now()->setTime(10, 0));
    Order::factory()->count(2)->create();
    Order::factory()->create(['created_at' => now()->subDay()]);

    $this->actingAs(User::factory()->admin()->create())->get('/admin')
        ->assertSeeInOrder(['Pesanan hari ini', '2'], false);
});
