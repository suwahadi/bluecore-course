# BlueCore Course

Aplikasi kursus video budidaya ikan dalam galon untuk course.bluecore.co.id.

## Stack

- PHP 8.3, Laravel 13, Livewire 4
- Tailwind CSS 4 lewat Vite
- MySQL atau MariaDB
- Pest 4 untuk test

## Menjalankan di lokal

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
```

## Test

```bash
php artisan test
```
