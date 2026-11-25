<section class="panel admin-order-panel screen-only">
    <h2 class="panel-title">Tindakan admin</h2>
    @include('admin.orders.actions', ['withView' => false])
    @include('admin.orders.dialogs')
    <h3 class="admin-order-panel__title">Riwayat callback</h3>
    @if ($order->callbacks->isEmpty())
        <p class="muted small">Belum ada callback dari Duitku.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Waktu</th><th>Kode hasil</th><th>Status proses</th></tr>
                </thead>
                <tbody>
                    @foreach ($order->callbacks as $callback)
                        <tr>
                            <td>{{ \App\Support\Format::datetime($callback->created_at) }}</td>
                            <td class="num">{{ $callback->result_code }}</td>
                            <td>{{ $callback->status->label() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
