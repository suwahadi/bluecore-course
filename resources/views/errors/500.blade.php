<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Terjadi kesalahan: {{ config('app.name') }}</title>
    <style>
        body { margin: 0; min-height: 100dvh; display: grid; place-items: center; padding: 24px; font-family: Poppins, system-ui, sans-serif; background: #F3F8FC; color: #0C2238; }
        main { max-width: 560px; text-align: center; }
        h1 { margin: 6px 0 0; font-size: 32px; color: #0C3865; }
        p { margin: 10px 0 0; color: #4B5B6B; line-height: 1.55; }
        a { display: inline-flex; align-items: center; min-height: 44px; margin-top: 20px; padding: 10px 20px; border-radius: 8px; background: #044C94; color: #FFFFFF; font-weight: 600; text-decoration: none; }
        a:hover { background: #0C3865; }
    </style>
</head>
<body>
    <main>
        <p>500</p>
        <h1>Terjadi kesalahan</h1>
        <p>Server sedang bermasalah. Coba muat ulang halaman beberapa saat lagi.</p>
        <a href="{{ url('/') }}">Kembali ke beranda</a>
    </main>
</body>
</html>
