<?php

use App\Models\User;

it('shows the sign-up gate to guests', function () {
    $this->get('/admin')->assertStatus(401)->assertViewIs('auth.required');
});

it('refuses users without the admin role', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('sends unverified admins to the verification notice', function () {
    $this->actingAs(User::factory()->admin()->unverified()->create())->get('/admin')
        ->assertRedirect(route('verification.notice'));
});

it('lets admins in and marks the current tab', function () {
    $response = $this->actingAs(User::factory()->admin()->create())->get('/admin')
        ->assertOk()
        ->assertSeeText('Panel admin');

    expect($response->getContent())->toMatch('#<a href="'.preg_quote(route('admin.dashboard'), '#').'"\s+aria-current="page"#');
});

it('hides the orders tab until its route exists', function () {
    $html = $this->actingAs(User::factory()->admin()->create())->get('/admin')->getContent();

    expect($html)->toContain('aria-label="Menu admin"')->not->toContain('<span>Pesanan</span>');
});
