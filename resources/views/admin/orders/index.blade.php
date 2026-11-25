@php
    use App\Enums\OrderStatus;
    use App\Support\Format;
@endphp
<x-admin.layout title="Pesanan">
    <x-form :action="route('admin.orders.index')" method="GET" class="toolbar toolbar--orders">
        <div class="field">
            <label for="filter-q">Cari nomor atau pembeli</label>
            <input id="filter-q" type="search" name="q" value="{{ $term }}">
        </div>
        <div class="field">
            <label for="filter-status">Status</label>
            <select id="filter-status" name="status" x-data x-on:change="$el.form.requestSubmit()">
                <option value="">Semua</option>
                @foreach (OrderStatus::cases() as $option)
                    <option value="{{ $option->value }}" @selected($status === $option)>{{ $option->label() }}</option>
                @endforeach
            </select>
        </div>
        <x-date-range :from="$from" :to="$to" />
        <x-button-pair class="toolbar__actions">
            <button type="submit" class="btn btn--brand">Terapkan</button>
            @if ($filtered)
                <a class="btn" href="{{ route('admin.orders.index') }}">Hapus filter</a>
            @endif
        </x-button-pair>
    </x-form>

    @if ($orders->isEmpty())
        <x-empty-state :message="$filtered ? 'Tidak ada pesanan yang cocok dengan filter ini.' : 'Belum ada pesanan.'">
            @if ($filtered)
                <a class="btn" href="{{ route('admin.orders.index') }}">Hapus filter</a>
            @endif
        </x-empty-state>
    @else
        <div class="panel table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Nomor</th><th>Pembeli</th><th>Kursus</th><th class="num">Total</th><th>Status</th><th>Dibuat</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td><a href="{{ route('order.show', $order) }}">{{ $order->order_number }}</a></td>
                            <td>{{ $order->user->name }}<div class="muted small">{{ $order->user->email }}</div></td>
                            <td>{{ $order->course->title }}</td>
                            <td class="num">{{ Format::rupiah($order->amount) }}</td>
                            <td><x-status-badge :status="$order->status" /></td>
                            <td class="muted">{{ Format::datetime($order->created_at) }}</td>
                            <td>@include('admin.orders.actions')</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $orders->links() }}
    @endif

    @foreach ($orders as $order)
        @include('admin.orders.dialogs')
    @endforeach
</x-admin.layout>
