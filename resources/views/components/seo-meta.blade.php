<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $metaDescription }}">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:image" content="{{ $imageUrl }}">
<meta property="og:url" content="{{ $canonical }}">
<link rel="canonical" href="{{ $canonical }}">
@if ($noindex)
<meta name="robots" content="noindex">
@endif
