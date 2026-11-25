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

it('shows every admin tab, orders included', function () {
    $html = $this->actingAs(User::factory()->admin()->create())->get('/admin')->getContent();

    expect($html)->toContain('aria-label="Menu admin"')->toContain('<span>Pesanan</span>');
});

it('scrolls the active admin tab into view on narrow screens', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/admin/settings')
        ->assertSee('<nav class="admin-tabs" aria-label="Menu admin" x-data="centeredTabs">', false);
});
