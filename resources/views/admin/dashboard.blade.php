@php
    use App\Support\Format;
    $ordersUrl = Route::has('admin.orders.index') ? route('admin.orders.index') : null;
    $kpis = [
        [
            'icon' => 'wallet', 'label' => 'Pendapatan', 'value' => Format::rupiah($revenue),
            'meta' => 'Dari '.$paidCount.' pesanan lunas', 'primary' => true,
            'href' => $ordersUrl ? route('admin.orders.index', ['status' => 'paid']) : null,
        ],
        [
            'icon' => 'receipt', 'label' => 'Pesanan hari ini', 'value' => $todayOrders,
            'meta' => $pendingCount ? $pendingCount.' menunggu pembayaran' : 'Tidak ada yang menunggu pembayaran',
            'href' => $ordersUrl,
        ],
        [
            'icon' => 'play', 'label' => 'Kursus terbit', 'value' => $publishedCourses,
            'meta' => $draftCourses ? $draftCourses.' masih draf' : 'Tidak ada draf',
            'href' => Route::has('admin.courses.index') ? route('admin.courses.index') : null,
        ],
        [
            'icon' => 'users', 'label' => 'Pengguna terdaftar', 'value' => $userCount,
            'meta' => $buyerCount.' sudah membeli kursus',
            'href' => Route::has('admin.users.index') ? route('admin.users.index') : null,
        ],
    ];
@endphp
<x-admin.layout title="Dasbor">
    <ul class="kpis">
        @foreach ($kpis as $kpi)
            @php
                $tag = $kpi['href'] ? 'a' : 'div';
                $primary = $kpi['primary'] ?? false;
            @endphp
            <li>
                <{{ $tag }} class="{{ $primary ? 'kpi kpi--primary' : 'kpi' }}" @if ($kpi['href']) href="{{ $kpi['href'] }}" @endif>
                    <span class="kpi__head"><span class="kpi__icon"><x-icon :name="$kpi['icon']" :size="20" /></span><span class="kpi__label">{{ $kpi['label'] }}</span></span>
                    <strong class="kpi__value num">{{ $kpi['value'] }}</strong>
                    <span class="kpi__meta">{{ $kpi['meta'] }}</span>
                    @if ($primary)
                        <span class="kpi__split">
                            <span><small>Bulan ini</small><b class="num">{{ Format::rupiah($monthRevenue) }}</b></span>
                            <span><small>Rata-rata per pesanan</small><b class="num">{{ Format::rupiah($averageOrder) }}</b></span>
                        </span>
                    @endif
                    <span class="kpi__ornament" aria-hidden="true"><x-icon :name="$kpi['icon']" :size="120" /></span>
                </{{ $tag }}>
            </li>
        @endforeach
    </ul>
    <div class="panel">
        <div class="panel__head">
            <h2 class="panel-title">Pesanan menunggu pembayaran</h2>
            @if ($ordersUrl)
                <a href="{{ $ordersUrl }}">Semua pesanan</a>
            @endif
        </div>
        @if ($pendingOrders->isEmpty())
            <x-empty-state message="Tidak ada pesanan yang menunggu pembayaran." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Nomor</th><th>Pembeli</th><th>Kursus</th><th class="num">Total</th><th>Status</th><th>Dibuat</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($pendingOrders as $order)
                            <tr>
                                <td class="num">{{ $order->order_number }}</td>
                                <td>{{ $order->user->name }}<div class="muted small">{{ $order->user->email }}</div></td>
                                <td>{{ $order->course->title }}</td>
                                <td class="num">{{ Format::rupiah($order->amount) }}</td>
                                <td><x-status-badge :status="$order->status" /></td>
                                <td>{{ Format::datetime($order->created_at) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-admin.layout>
