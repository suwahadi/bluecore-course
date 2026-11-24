<x-layouts.app title="Video gratis">
    <section class="section">
        <div class="wrap narrow">
            <x-empty-state message="Video gratis belum tersedia.">
                <a class="btn btn--brand" href="{{ route('courses.index') }}">Lihat kursus</a>
            </x-empty-state>
        </div>
    </section>
</x-layouts.app>
