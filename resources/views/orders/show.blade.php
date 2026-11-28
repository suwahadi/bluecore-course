@php
    use App\Enums\OrderStatus;
    use App\Support\Format;
    use App\Support\Settings;
    $status = $order->status;
    $pastDue = $status === OrderStatus::Pending && $order->expires_at?->isPast();
    $note = match (true) {
        $pastDue => ['warn', 'clock', 'Batas waktu bayar sudah lewat', 'Pesanan ini akan segera kedaluwarsa. Jika kamu tetap membayar lewat halaman Duitku, pembayaran tetap diproses dan akses kursus dibuka.'],
        $status === OrderStatus::Pending => ['warn', 'clock', 'Menunggu pembayaran', 'Selesaikan pembayaran sebelum '.Format::datetime($order->expires_at).'. Akses kursus terbuka otomatis setelah pembayaran diterima.'],
        $status === OrderStatus::Paid => ['ok', 'check', 'Pembayaran diterima', 'Lunas pada '.Format::datetime($order->paid_at).'. Akses kursus sudah terbuka.'],
        $status === OrderStatus::Expired => ['muted', 'clock', 'Pesanan kedaluwarsa', 'Pesanan tidak dibayar sampai batas waktu. Buat pesanan baru untuk membeli kursus ini.'],
        $status === OrderStatus::Cancelled => ['muted', 'x', 'Pesanan dibatalkan', 'Buat pesanan baru jika ingin membeli kursus ini.'],
        $status === OrderStatus::Failed => ['bad', 'x', 'Pembayaran gagal', 'Buat pesanan baru untuk mencoba lagi dengan metode lain.'],
        default => ['muted', 'refresh', 'Dana dikembalikan', 'Dana pesanan ini sudah dikembalikan pada '.Format::datetime($order->refunded_at ?? $order->updated_at).'. Akses kursus ditutup.'],
    };
    [$tone, $icon, $noteTitle, $noteText] = $note;
    $dueLabel = match ($status) {
        OrderStatus::Paid => 'Total dibayar',
        OrderStatus::Pending => 'Total tagihan',
        default => 'Total',
    };
    $course = $order->course;
    $payable = $isOwner && $status === OrderStatus::Pending && $order->duitku_reference && $order->payment_url;
    $contactLine = implode(', ', array_filter([Settings::get('email'), Settings::get('phone')]));
@endphp
<x-layouts.app :title="'Pesanan '.$order->order_number" :noindex="true">
    <section class="section">
        <div class="wrap order-page">
            <p class="print-only print-head">
                <strong>{{ Settings::get('site_name') }}</strong>
                @if (filled(Settings::get('address')))
                    <br>{{ Settings::get('address') }}
                @endif
                @if ($contactLine !== '')
                    <br>{{ $contactLine }}
                @endif
            </p>
            <a class="back-link" href="{{ $isOwner ? url('/orders') : url('/admin/orders') }}"><x-icon name="back" />{{ $isOwner ? 'Pesanan saya' : 'Semua pesanan' }}</a>
            <article class="panel invoice">
                <header class="invoice__head">
                    <div>
                        <p class="invoice__label"><span class="screen-only">Nomor pesanan</span><span class="print-only">Invoice</span></p>
                        <div class="invoice__number" x-data="{ copied: false }">
                            <h1 class="num">{{ $order->order_number }}</h1>
                            <button type="button" class="copy-btn" aria-label="Salin nomor pesanan" title="Salin nomor pesanan" x-bind:data-copied="copied.toString()" x-on:click="navigator.clipboard?.writeText('{{ $order->order_number }}'); copied = true; setTimeout(() => copied = false, 2000)"><x-icon name="copy" /></button>
                            <span class="sr-only" role="status" x-text="copied ? 'Nomor pesanan disalin' : ''"></span>
                        </div>
                        <p class="invoice__date">Dibuat {{ Format::datetime($order->created_at) }}</p>
                    </div>
                    <p class="invoice__due"><span>{{ $dueLabel }}</span><strong class="num">{{ Format::rupiah($order->amount) }}</strong></p>
                </header>
                <div class="status-note status-note--{{ $tone }}">
                    <span class="status-note__icon"><x-icon :name="$icon" :size="20" /></span>
                    <div><strong>{{ $noteTitle }}</strong><p>{{ $noteText }}</p></div>
                </div>
                <div class="invoice__item">
                    <a href="{{ route('courses.show', $course) }}" tabindex="-1" aria-hidden="true">
                        @if ($course->thumbnail_path)
                            <img class="thumb" src="{{ Storage::disk('public')->url($course->thumbnail_path) }}" alt="" width="1200" height="675">
                        @else
                            <x-thumbnail-placeholder :title="$course->title" />
                        @endif
                    </a>
                    <div>
                        <p class="invoice__cat">{{ $course->category?->name }}</p>
                        <a class="invoice__course" href="{{ route('courses.show', $course) }}">{{ $course->title }}</a>
                        <x-course-meta :course="$course" />
                    </div>
                </div>
                <dl class="invoice__meta">
                    <div><dt>Pembeli</dt><dd>{{ $order->user->name }}</dd></div>
                    @if ($status === OrderStatus::Pending)
                        <div><dt>Bayar sebelum</dt><dd>{{ Format::datetime($order->expires_at) }}</dd></div>
                    @endif
                    @if ($order->paid_at)
                        <div><dt>Dibayar</dt><dd>{{ Format::datetime($order->paid_at) }}</dd></div>
                    @endif
                    @if ($paymentName)
                        <div><dt>Metode bayar</dt><dd>{{ $paymentName }}</dd></div>
                    @endif
                    @if ($order->confirmedBy)
                        <div><dt>Ditandai lunas oleh</dt><dd>{{ $order->confirmedBy->name }}</dd></div>
                    @endif
                </dl>
                <div class="invoice__totals">
                    <div class="summary-line"><span>Harga kursus</span><span class="num">{{ Format::rupiah($order->subtotal) }}</span></div>
                    @if ($order->admin_fee > 0)
                        <div class="summary-line"><span>Biaya admin</span><span class="num">{{ Format::rupiah($order->admin_fee) }}</span></div>
                    @endif
                    <div class="summary-line summary-line--total"><span>Total</span><span class="num">{{ Format::rupiah($order->amount) }}</span></div>
                </div>
                @if ($isOwner)
                    <footer class="invoice__actions screen-only">
                        @if ($status === OrderStatus::Pending)
                            <div class="order-pay" x-data="orderPayment($el.dataset)" data-reference="{{ $order->duitku_reference }}" data-payment-url="{{ $order->payment_url }}" data-refresh-url="{{ route('order.refresh', $order) }}" data-status-url="{{ route('order.status', $order) }}" data-auto-open="{{ $autoOpen && $payable ? 'true' : 'false' }}">
                                <div class="btn-row">
                                    @if ($payable)
                                        <a class="btn btn--primary" href="{{ $order->payment_url }}" x-on:click.prevent="pay()">Bayar sekarang</a>
                                    @endif
                                    <x-form :action="route('order.refresh', $order)" class="inline-form">
                                        <button type="submit" class="btn">Perbarui status</button>
                                    </x-form>
                                </div>
                                <p class="muted small order-pay__message" role="status" x-show="message" x-text="message" x-cloak></p>
                            </div>
                            <div x-data>
                                <button type="button" class="btn btn--quiet-danger" x-on:click="$dispatch('open-modal', 'cancel-order')">Batalkan pesanan</button>
                                <x-modal name="cancel-order" title="Batalkan pesanan?">
                                    <p>Pesanan {{ $order->order_number }} akan dibatalkan. Jika kamu tetap membayar lewat tautan lama, pembayaran tetap diproses.</p>
                                    <x-form :action="route('order.cancel', $order)">
                                        <x-button-pair>
                                            <button type="button" class="btn" x-on:click="open = false">Batal</button>
                                            <button type="submit" class="btn btn--danger">Ya, batalkan</button>
                                        </x-button-pair>
                                    </x-form>
                                </x-modal>
                            </div>
                        @elseif ($status === OrderStatus::Paid)
                            <div class="btn-row">
                                <a class="btn btn--primary" href="{{ route('learn.index', $course) }}">Mulai belajar</a>
                                <button type="button" class="btn" x-data x-on:click="window.print()">Cetak invoice</button>
                            </div>
                        @else
                            <div class="btn-row">
                                <a class="btn btn--primary" href="{{ route('checkout.show', $course) }}">Beli lagi</a>
                            </div>
                        @endif
                    </footer>
                @endif
            </article>
            @if (auth()->user()->isAdmin())
                @include('orders.partials.admin-panel')
            @endif
            @if ($payable)
                <script src="{{ config('duitku.js_url') }}" defer></script>
            @endif
        </div>
    </section>
</x-layouts.app>
