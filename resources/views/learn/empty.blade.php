<x-layouts.app :title="$course->title" :noindex="true">
    <section class="section">
        <div class="wrap">
            <div class="page-head"><h1>{{ $course->title }}</h1></div>
            <x-empty-state message="Video kursus ini belum tersedia.">
                <a class="btn btn--brand" href="{{ route('my-courses') }}">Kursus saya</a>
            </x-empty-state>
        </div>
    </section>
</x-layouts.app>
