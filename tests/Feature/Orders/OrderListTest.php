<?php

use App\Enums\OrderStatus;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
});

function listOrder(string $createdAt, array $attributes = []): Order
{
    return Order::factory()->for(test()->user)->for(Course::factory()->published()->create(['title' => 'Kursus '.$createdAt]))->create([
        'created_at' => Carbon::parse($createdAt, 'Asia/Jakarta'), ...$attributes,
    ]);
}

it('lists only my orders, newest first, with number, course, total, status and date', function () {
    $older = listOrder('2026-11-01 09:00', ['amount' => 99000]);
    $newer = listOrder('2026-11-20 09:00', ['status' => OrderStatus::Paid, 'paid_at' => now(), 'amount' => 150000]);
    Order::factory()->create(['amount' => 777000]);

    $this->actingAs($this->user)->get('/orders')
        ->assertOk()
        ->assertSee('<h1>Pesanan saya</h1>', false)
        ->assertSeeInOrder([$newer->order_number, 'Kursus 2026-11-20 09:00', 'Rp 150.000', 'Lunas', $older->order_number, 'Rp 99.000', 'Menunggu pembayaran'])
        ->assertSee('href="'.route('order.show', $newer).'"', false)
        ->assertDontSeeText('Rp 777.000');
});

it('filters by Jakarta calendar days, including the last minute of the end date', function () {
    $before = listOrder('2026-11-09 23:59');
    $start = listOrder('2026-11-10 00:00');
    $end = listOrder('2026-11-12 23:59');
    $after = listOrder('2026-11-13 00:00');

    $this->actingAs($this->user)->get('/orders?from=2026-11-10&to=2026-11-12')
        ->assertSeeText($start->order_number)
        ->assertSeeText($end->order_number)
        ->assertDontSeeText($before->order_number)
        ->assertDontSeeText($after->order_number)
        ->assertSee('value="2026-11-10"', false)
        ->assertSeeText('Terapkan')
        ->assertSee('>Hapus filter</a>', false);
});

it('rejects an end date before the start date with the PRD message', function () {
    $this->actingAs($this->user)->followingRedirects()->get('/orders?from=2026-11-12&to=2026-11-10')
        ->assertOk()
        ->assertSee('id="to-error" role="alert">Tanggal akhir harus sama dengan atau setelah tanggal awal.', false);
});

it('rejects dates in the wrong format', function () {
    $this->actingAs($this->user)->followingRedirects()->get('/orders?from=10-11-2026')
        ->assertOk()
        ->assertSee('id="from-error" role="alert">Tanggal harus berformat tahun-bulan-tanggal.', false);
});

it('shows an empty state with a link to the catalog when there are no orders', function () {
    $this->actingAs($this->user)->get('/orders')
        ->assertSeeText('Belum ada pesanan. Pesanan muncul di sini setelah Anda membeli kursus.')
        ->assertSee('href="'.route('courses.index').'"', false)
        ->assertDontSeeText('Terapkan');
});

it('shows an empty state with Hapus filter when the filter finds nothing', function () {
    listOrder('2026-11-01 09:00');

    $this->actingAs($this->user)->get('/orders?from=2026-12-01')
        ->assertSeeText('Tidak ada pesanan pada rentang tanggal ini.')
        ->assertSee('>Hapus filter</a>', false);
});

it('asks guests to sign up', function () {
    $this->get('/orders')->assertStatus(401);
});
