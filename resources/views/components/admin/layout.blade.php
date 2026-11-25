@props(['title'])
@php
    $tabs = [
        ['admin.dashboard', 'admin.dashboard', 'Dasbor', 'dashboard'],
        ['admin.courses.index', 'admin.courses.*', 'Kursus', 'play'],
        ['admin.categories.index', 'admin.categories.*', 'Kategori', 'folder'],
        ['admin.orders.index', 'admin.orders.*', 'Pesanan', 'receipt'],
        ['admin.users.index', 'admin.users.*', 'Pengguna', 'users'],
        ['admin.testimonials.index', 'admin.testimonials.*', 'Testimoni', 'quote'],
        ['admin.pages.index', 'admin.pages.*', 'Halaman', 'file'],
        ['admin.settings.edit', 'admin.settings.*', 'Pengaturan', 'sliders'],
    ];
@endphp
<x-layouts.app :title="'Admin '.$title" :noindex="true">
    <section class="section">
        <div class="wrap">
            <p class="muted small">Panel admin</p>
            <h1 class="admin-title">{{ $title }}</h1>
            <nav class="admin-tabs" aria-label="Menu admin" x-data="centeredTabs">
                @foreach ($tabs as [$route, $pattern, $label, $icon])
                    @if (Route::has($route))
                        <a href="{{ route($route) }}" @if (request()->routeIs($pattern)) aria-current="page" @endif><x-icon :name="$icon" /><span>{{ $label }}</span></a>
                    @endif
                @endforeach
            </nav>
            {{ $slot }}
        </div>
    </section>
</x-layouts.app>
