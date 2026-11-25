@php
    use App\Support\Format;
@endphp
<x-layouts.app title="Checkout" :noindex="true">
    <section class="section">
        <div class="wrap">
            <div class="page-head">
                <h1>Checkout</h1>
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
                        <button type="submit" class="btn btn--primary btn--block">Bayar sekarang</button>
                    </x-form>
                    <p class="muted small checkout-note">Anda akan diarahkan ke halaman pembayaran Duitku. Batas waktu bayar {{ $deadline }}.</p>
                </aside>
            </div>
        </div>
    </section>
</x-layouts.app>
