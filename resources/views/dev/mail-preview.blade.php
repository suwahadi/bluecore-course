<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Pratinjau email: {{ config('app.name') }}</title>
    <style>
        body { margin: 0; padding: 24px; font-family: Poppins, system-ui, sans-serif; background: #F3F8FC; color: #0C2238; }
        h1 { margin: 0; font-size: 24px; color: #0C3865; }
        .intro { margin: 6px 0 24px; color: #4B5B6B; }
        .grid { display: grid; gap: 24px; grid-template-columns: repeat(auto-fill, minmax(min(100%, 640px), 1fr)); }
        .card { background: #FFFFFF; border: 1px solid #D5E1EC; border-radius: 12px; overflow: hidden; }
        .card header { display: flex; justify-content: space-between; gap: 12px; align-items: baseline; padding: 12px 16px; border-bottom: 1px solid #D5E1EC; }
        .card h2 { margin: 0; font-size: 16px; }
        .card a { color: #044C94; font-size: 14px; }
        iframe { display: block; width: 100%; height: 820px; border: 0; }
    </style>
</head>
<body>
    <h1>Pratinjau email</h1>
    <p class="intro">Hanya tersedia di lingkungan lokal. Data di dalam email adalah contoh dan tidak tersimpan.</p>
    <div class="grid">
        @foreach ($previews as $key => $preview)
            <section class="card">
                <header>
                    <h2>{{ $preview['label'] }}</h2>
                    <a href="{{ route('dev.mail.show', $key) }}" target="_blank" rel="noopener">Buka sendiri</a>
                </header>
                <iframe src="{{ route('dev.mail.show', $key) }}" title="Pratinjau email {{ $preview['label'] }}" loading="lazy"></iframe>
            </section>
        @endforeach
    </div>
</body>
</html>
