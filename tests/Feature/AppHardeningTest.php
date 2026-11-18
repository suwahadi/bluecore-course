<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rules\Password;

it('blocks lazy loading outside production', function () {
    // Laravel hanya menandai lazy loading jika lebih dari satu model dimuat sekaligus.
    Course::factory()->count(2)->published()->create();

    expect(fn () => Course::all()->first()->category)->toThrow(LazyLoadingViolationException::class);
});

it('lets only admins through the admin middleware', function () {
    Route::middleware(['web', 'auth', 'admin'])->get('/_admin-probe', fn () => 'ok');

    $this->actingAs(User::factory()->create())->get('/_admin-probe')->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get('/_admin-probe')->assertOk();
});

it('requires passwords of at least 8 characters by default', function () {
    $validator = validator(['password' => 'pendek'], ['password' => Password::defaults()]);

    expect($validator->fails())->toBeTrue();
});
