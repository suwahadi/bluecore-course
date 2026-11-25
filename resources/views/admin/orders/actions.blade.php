@php
    use App\Enums\OrderStatus;
    $withView ??= true;
@endphp
<div class="btn-row">
    @if ($withView)
        <x-admin.icon-button icon="eye" label="Lihat pesanan" :href="route('order.show', $order)" />
    @endif
    @if (in_array($order->status, \App\Http\Controllers\Admin\OrderController::MARKABLE, true))
        <x-admin.icon-button icon="check" label="Tandai lunas" :opens="'mark-paid-'.$order->id" />
    @endif
    @if ($order->status === OrderStatus::Pending)
        <x-admin.confirm-action :dialog="'cancel-'.$order->id" :action="route('admin.orders.cancel', $order)" method="POST" icon="x" label="Batalkan pesanan" title="Batalkan pesanan?" :message="'Pesanan '.$order->order_number.' akan dibatalkan. Jika pembeli tetap membayar lewat tautan lama, pembayaran tetap diproses.'" confirm-label="Ya, batalkan" />
    @endif
    <x-admin.action-button :action="route('admin.orders.check-status', $order)" icon="refresh" label="Cek status ke Duitku" />
    @if ($order->status === OrderStatus::Paid)
        <x-admin.icon-button icon="wallet" label="Catat pengembalian dana" :opens="'refund-'.$order->id" />
    @endif
</div>
