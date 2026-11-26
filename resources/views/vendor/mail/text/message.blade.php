@php
    use App\Support\Settings;
    $siteName = (string) Settings::get('site_name');
    $supportEmail = Settings::get('email');
    $whatsapp = Settings::get('whatsapp');
@endphp
<x-mail::layout>
<x-slot:header>
<x-mail::header :url="url('/')">
{{ $siteName }}
</x-mail::header>
</x-slot:header>

{{ $slot }}

@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{{ $subcopy }}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

<x-slot:footer>
<x-mail::footer>
{{ $siteName }}
@if (filled($supportEmail))
Email: {{ $supportEmail }}
@endif
@if (filled($whatsapp))
WhatsApp: {{ \App\Support\WhatsApp::display($whatsapp) }}
@endif
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
