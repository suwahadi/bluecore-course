@if (in_array($order->status, \App\Http\Controllers\Admin\OrderController::MARKABLE, true))
    <x-admin.dialog-form :dialog="'mark-paid-'.$order->id" :action="route('admin.orders.mark-paid', $order)" title="Tandai lunas (override admin)" submit-label="Tandai lunas">
        <p class="muted small">Pesanan {{ $order->order_number }}, {{ $order->user->name }}, {{ \App\Support\Format::rupiah($order->amount) }}.</p>
        <x-field name="paid_note" label="Catatan referensi pembayaran" required maxlength="200" />
    </x-admin.dialog-form>
@endif
@if ($order->status === \App\Enums\OrderStatus::Paid)
    <x-admin.dialog-form :dialog="'refund-'.$order->id" :action="route('admin.orders.refund', $order)" title="Catat pengembalian dana" submit-label="Catat pengembalian">
        <p class="muted small">Akses {{ $order->user->name }} ke kursus ini akan ditutup.</p>
        <x-field name="refund_note" label="Catatan pengembalian" required maxlength="200" placeholder="misalnya bank dan tanggal transfer" />
    </x-admin.dialog-form>
@endif
