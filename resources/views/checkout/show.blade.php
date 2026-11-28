@php
    use App\Support\Format;
    use App\Support\Settings;
    use App\Support\WhatsApp;
    $whatsapp = Settings::get('whatsapp');
    $helpContacts = array_filter([$whatsapp ? 'WhatsApp '.WhatsApp::display($whatsapp) : null, Settings::get('email')]);
    $guarantee = Settings::get('checkout_guarantee');
@endphp
<x-layouts.app title="Checkout" :noindex="true">
    <section class="section">
        <div class="wrap">
            <div class="page-head">
                <h1>Satu Langkah Lagi untuk Mulai Menyiapkan Galonmu</h1>
                <p class="checkout-lead">Belajar sebelum beli bibit, agar keuntungan maksimal sejak panen pertama!</p>
            </div>
            <div class="two-col checkout-layout">
                <div class="panel checkout-item">
                    @if ($course->thumbnail_path)
                        <img class="thumb thumb--rounded" src="{{ Storage::disk('public')->url($course->thumbnail_path) }}" alt="Thumbnail {{ $course->title }}" width="1200" height="675">
                    @else
                        <x-thumbnail-placeholder :title="$course->title" class="thumb--rounded" />
                    @endif
                    <div>
                        <div class="panel__head">
                            <h2 class="panel-title">{{ $course->title }}</h2>
                            <div class="price-block price-block--sm"><x-price :course="$course" /></div>
                        </div>
                        <p class="muted small">{{ $course->category?->name }}</p>
                        <x-course-meta :course="$course" class="checkout-meta" />
                        @if (filled($course->short_description))
                            <p class="checkout-summary">{{ $course->short_description }}</p>
                        @endif
                        <h3 class="checkout-gets__title">Kamu akan mendapatkan</h3>
                        <ul class="checkout-gets">
                            <li><x-icon name="check" :size="16" />Bayar sekali, tanpa langganan.</li>
                            <li><x-icon name="check" :size="16" />Pembayaran melalui halaman resmi Duitku: QRIS, transfer bank, dan e-wallet.</li>
                            <li><x-icon name="check" :size="16" />Kursus otomatis muncul di menu Kursus saya setelah pembayaran terverifikasi.</li>
                            @if ($helpContacts)
                                <li><x-icon name="check" :size="16" />Bantuan akses melalui {{ implode(' atau ', $helpContacts) }}.</li>
                            @endif
                            @if (filled($guarantee))
                                <li><x-icon name="check" :size="16" />{{ $guarantee }}</li>
                            @endif
                        </ul>
                    </div>
                </div>
                <aside class="panel">
                    <h2 class="panel-title">Ringkasan</h2>
                    <div class="checkout-lines">
                        <div class="summary-line"><span>Harga kursus</span><span class="num">{{ Format::rupiah($course->price) }}</span></div>
                        @if ($adminFee > 0)
                            <div class="summary-line"><span>Biaya admin</span><span class="num">{{ Format::rupiah($adminFee) }}</span></div>
                        @endif
                        <div class="summary-line summary-line--total"><span>Total</span><span class="num">{{ Format::rupiah($total) }}</span></div>
                    </div>
                    <x-form :action="route('checkout.store', $course)">
                        <button type="submit" class="btn btn--primary btn--block">Bayar {{ Format::rupiah($total) }}</button>
                    </x-form>
                    <p class="muted small checkout-note">Selesaikan pembayaran dalam {{ $deadline }}. Setelah itu, pesanan akan kedaluwarsa dan perlu dibuat ulang. Makin cepat dibayar, makin cepat kamu bisa mulai menyiapkan galon.</p>
                </aside>
            </div>
        </div>
    </section>
</x-layouts.app>
