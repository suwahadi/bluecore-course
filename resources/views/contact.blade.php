<x-layouts.app title="Kontak">
    <section class="section">
        <div class="wrap">
            <div class="page-head">
                <div>
                    <h1>Kontak</h1>
                    <p class="muted">Ada pertanyaan soal kursus, pembayaran, atau akses? Hubungi kami.</p>
                </div>
            </div>
            <div class="contact-layout">
                <div class="contact-card">
                    @foreach ($groups as $title => $rows)
                        @if ($rows !== [] || $loop->first)
                            <div class="contact-card__group">
                                <h2 class="contact-card__title">{{ $title }}</h2>
                                @if ($rows === [])
                                    <p>Informasi kontak belum diisi.</p>
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
                        @endif
                    @endforeach
                </div>
                @if ($canMessage)
                    <div class="panel contact-form">
                        <h2 class="panel-title">Kirim pesan</h2>
                        <x-form :action="route('contact.send')" class="contact-form__fields">
                            <x-field name="name" label="Nama" :value="auth()->user()?->name" required maxlength="100" autocomplete="name" />
                            <x-field name="message" label="Pesan" type="textarea" required maxlength="1000" rows="5" placeholder="Tulis pertanyaan Anda" />
                            <button type="submit" class="btn btn--primary btn--block">Kirim Sekarang</button>
                        </x-form>
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-layouts.app>
