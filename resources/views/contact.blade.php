<x-layouts.app title="Kontak">
    <section class="section">
        <div class="wrap prose page-layout">
            <h1>Kontak</h1>
            <div class="panel panel--prose panel--fit">
                @if ($rows === [])
                    <p class="muted">Informasi kontak belum diisi.</p>
                @else
                    <ul class="contact-list">
                        @foreach ($rows as $row)
                            <li>
                                @if ($row['url'])
                                    <a class="contact-list__item" href="{{ $row['url'] }}" @if (str_starts_with($row['url'], 'http')) target="_blank" rel="noopener" @endif>
                                        <span class="contact-list__icon"><x-icon :name="$row['icon']" :size="18" :solid="true" /></span>
                                        <span><span class="sr-only">{{ $row['label'] }}: </span>{{ $row['text'] }}</span>
                                    </a>
                                @else
                                    <span class="contact-list__item">
                                        <span class="contact-list__icon"><x-icon :name="$row['icon']" :size="18" :solid="true" /></span>
                                        <span><span class="sr-only">{{ $row['label'] }}: </span>{{ $row['text'] }}</span>
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </section>
</x-layouts.app>
