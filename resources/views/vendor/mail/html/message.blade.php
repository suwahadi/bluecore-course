@php
    use App\Support\Settings;
    $siteName = (string) Settings::get('site_name');
    $supportEmail = Settings::get('email');
    $whatsapp = Settings::get('whatsapp');
    $contacts = array_values(array_filter([
        filled($supportEmail) ? 'email <a href="mailto:'.e($supportEmail).'">'.e($supportEmail).'</a>' : null,
        filled($whatsapp) ? 'WhatsApp <a href="https://wa.me/'.e($whatsapp).'">0'.e(substr($whatsapp, 2)).'</a>' : null,
    ]));
@endphp
<x-mail::layout>
<x-slot:header>
<x-mail::header :url="url('/')">
{{ $siteName }}
</x-mail::header>
</x-slot:header>

{!! $slot !!}

@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

<x-slot:footer>
<x-mail::footer>
<p><strong>{{ $siteName }}</strong></p>
<p>Ada pertanyaan? Balas email ini{!! $contacts ? ' atau hubungi kami lewat '.implode(' dan ', $contacts) : '' !!}.</p>
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
