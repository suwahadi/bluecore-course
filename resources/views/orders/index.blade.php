@php
    use App\Support\Format;
@endphp
<x-layouts.app title="Pesanan saya" :noindex="true">
    <section class="section">
        <div class="wrap">
            <div class="page-head">
                <h1>Pesanan saya</h1>
            </div>
            @if ($hasOrders || $errors->hasAny(['from', 'to']))
                <x-form :action="route('orders.index')" method="GET" class="toolbar toolbar--dates">
                    <x-date-range :from="$from" :to="$to" />
                    <x-button-pair class="toolbar__actions">
                        <button type="submit" class="btn btn--brand">Terapkan</button>
                        @if ($filtered)
                            <a class="btn" href="{{ route('orders.index') }}">Hapus filter</a>
                        @endif
                    </x-button-pair>
                </x-form>
            @endif

            @if ($orders->isNotEmpty())
                <div class="panel table-wrap">
                    <table class="table">
                        <thead>
                            <tr><th>Nomor</th><th>Kursus</th><th class="num">Total</th><th>Status</th><th>Dibuat</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr>
                                    <td><a href="{{ route('order.show', $order) }}">{{ $order->order_number }}</a></td>
                                    <td>{{ $order->course->title }}</td>
                                    <td class="num">{{ Format::rupiah($order->amount) }}</td>
                                    <td><x-status-badge :status="$order->status" /></td>
                                    <td class="muted">{{ Format::datetime($order->created_at) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $orders->links() }}
            @elseif ($filtered)
                <x-empty-state message="Tidak ada pesanan pada rentang tanggal ini.">
                    <a class="btn" href="{{ route('orders.index') }}">Hapus filter</a>
                </x-empty-state>
            @else
                <x-empty-state message="Belum ada pesanan. Pesanan muncul di sini setelah Anda membeli kursus.">
                    <a class="btn btn--brand" href="{{ route('courses.index') }}">Lihat kursus</a>
                </x-empty-state>
            @endif
        </div>
    </section>
</x-layouts.app>
