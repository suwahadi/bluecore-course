@props(['rows'])
@foreach ($rows as $row)
{{ \Illuminate\Support\Str::squish((string) $row[0]) }}: {{ \Illuminate\Support\Str::squish((string) $row[1]) }}
@endforeach
