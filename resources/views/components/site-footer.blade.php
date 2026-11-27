<footer class="site-footer">
    <div class="site-footer__inner">
        <div>
            <p><strong>{{ $siteName }}</strong></p>
            @if ($slogan)
                <p class="site-footer__tagline">{{ $slogan }}</p>
            @endif
            @if ($description)
                <p class="muted small mt-1.5 max-w-[40ch]">{{ $description }}</p>
            @endif
        </div>
        <div>
            <h2 class="site-footer__title">Tautan</h2>
            <ul>
                <li><a href="{{ url('/courses') }}">Kursus</a></li>
                @foreach ($pages as $page)
                    <li><a href="{{ url($page['slug']) }}">{{ $page['title'] }}</a></li>
                @endforeach
                <li><a href="{{ url('/contact') }}">Hubungi Kami</a></li>
            </ul>
        </div>
        @foreach (['Kontak' => $contacts, 'Sosial Media' => $socials] as $title => $rows)
            @if ($rows)
                <div>
                    <h2 class="site-footer__title">{{ $title }}</h2>
                    <ul class="site-footer__contact">
                        @foreach ($rows as $contact)
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
        @endforeach
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
    @if ($disclaimer)
        <p class="site-footer__disclaimer">{{ $disclaimer }}</p>
    @endif
    <p class="site-footer__bottom">&copy; {{ now()->year }} {{ $owner }}. Hak cipta dilindungi. Pembayaran diproses oleh Duitku.</p>
</footer>
