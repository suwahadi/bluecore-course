<x-layouts.app title="Kontak">
    <section class="section">
        <div class="wrap prose">
            <h1>Kontak</h1>
            @if (blank($address) && blank($phone) && blank($whatsapp) && blank($email))
                <p class="muted">Informasi kontak belum diisi.</p>
            @else
                <dl class="dl contact-list">
                    @if (filled($address))
                        <dt>Alamat</dt>
                        <dd>{{ $address }}</dd>
                    @endif
                    @if (filled($phone))
                        <dt>Telepon</dt>
                        <dd>{{ $phone }}</dd>
                    @endif
                    @if (filled($whatsapp))
                        <dt>WhatsApp</dt>
                        <dd><a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener">Chat lewat WhatsApp</a></dd>
                    @endif
                    @if (filled($email))
                        <dt>Email</dt>
                        <dd><a href="mailto:{{ $email }}">{{ $email }}</a></dd>
                    @endif
                </dl>
            @endif
        </div>
    </section>
</x-layouts.app>
