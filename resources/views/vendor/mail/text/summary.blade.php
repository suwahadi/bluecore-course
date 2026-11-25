@props(['rows'])
@foreach ($rows as $row)
{{ $row[0] }}: {{ $row[1] }}
@endforeach
