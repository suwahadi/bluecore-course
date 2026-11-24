{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($staticUrls as $url)
    <url><loc>{{ $url }}</loc></url>
@endforeach
@foreach ($courses as $course)
    <url><loc>{{ route('courses.show', $course) }}</loc><lastmod>{{ $course->updated_at?->format('Y-m-d') }}</lastmod></url>
@endforeach
@foreach ($pages as $page)
    <url><loc>{{ route('page.show', $page) }}</loc><lastmod>{{ $page->updated_at?->format('Y-m-d') }}</lastmod></url>
@endforeach
</urlset>
