@props(['title'])
<span {{ $attributes->merge(['class' => 'thumb']) }} role="img" aria-label="Thumbnail {{ $title }}">
    <svg viewBox="0 0 320 180" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <rect width="320" height="180" fill="#E4FAFE"/>
        <path d="M0 136c40-14 80-14 120 0s80 14 120 0 80-14 80 0v44H0z" fill="#AEEFFF"/>
        <path d="M0 154c40-12 80-12 120 0s80 12 120 0 80-12 80 0v26H0z" fill="#4AC3F5" opacity="0.5"/>
        <g fill="#044C94">
            <path d="M94 86L56 60v52z"/>
            <path d="M92 86C120 48 190 48 228 86 190 124 120 124 92 86z"/>
            <path d="M146 62l16-18 10 24z"/>
            <path d="M150 110l16 16 8-22z" opacity="0.85"/>
        </g>
        <path d="M194 70c-6 10-6 22 0 32" fill="none" stroke="#E4FAFE" stroke-width="2.5" stroke-linecap="round" opacity="0.7"/>
        <circle cx="206" cy="80" r="5" fill="#FFFFFF"/>
        <circle cx="208" cy="80" r="2.5" fill="#0C3865"/>
        <text x="20" y="40" fill="#0C3865" font-family="Ageo, Urbanist, sans-serif" font-weight="800" font-size="28">{{ \App\Support\Format::initials($title) }}</text>
    </svg>
</span>
