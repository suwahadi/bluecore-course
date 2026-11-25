<footer class="site-footer">
    <div class="site-footer__inner">
        <div>
            <p><strong>{{ $siteName }}</strong></p>
            @if ($description)
                <p class="muted small mt-1.5 max-w-[40ch]">{{ $description }}</p>
            @endif
        </div>
        <div>
            <h2 class="site-footer__title">Tautan</h2>
            <ul>
                <li><a href="{{ url('/courses') }}">Semua kursus</a></li>
                @foreach ($pages as $page)
                    <li><a href="{{ url($page['slug']) }}">{{ $page['title'] }}</a></li>
                @endforeach
                <li><a href="{{ url('/contact') }}">Kontak</a></li>
            </ul>
        </div>
        @if ($contacts)
            <div>
                <h2 class="site-footer__title">Hubungi kami</h2>
                <ul class="site-footer__contact">
                    @foreach ($contacts as $contact)
                        <li>
                            <a href="{{ $contact['url'] }}" @if (str_starts_with($contact['url'], 'http')) rel="noopener" target="_blank" @endif>
                                <span class="site-footer__icon"><x-icon :name="$contact['icon']" :size="16" :solid="true" /></span>
                                <span class="sr-only">{{ $contact['label'] }}: </span>
                                <span>{{ $contact['text'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if ($markets)
            <div>
                <h2 class="site-footer__title">Marketplace</h2>
                <ul class="site-footer__markets">
                    @foreach ($markets as $market)
                        <li>
                            <a class="market-tile" href="{{ $market['url'] }}" rel="noopener" target="_blank" title="{{ $market['name'] }}">
                                <img src="{{ asset('images/marketplace/'.$market['image'].'.webp') }}" alt="{{ $market['name'] }}" width="{{ $market['width'] }}" height="{{ $market['height'] }}" loading="lazy">
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
    <p class="site-footer__bottom">&copy; {{ now()->year }} {{ $siteName }}. Hak cipta dilindungi.</p>
</footer>
