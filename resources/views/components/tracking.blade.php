@php
    use App\Support\Tracking;
    $ga4 = \App\Support\Settings::get('ga4_measurement_id');
    $pixel = \App\Support\Settings::get('meta_pixel_id');
    $enabled = ($ga4 || $pixel)
        && ! request()->routeIs('admin.*', 'password.reset', 'verification.verify')
        && ! request()->is('admin', 'admin/*')
        && ! auth()->user()?->isAdmin();
    $events = $enabled ? Tracking::pull() : [];
@endphp
@if ($enabled && $ga4)
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4 }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', {{ Js::from($ga4) }});
    @foreach ($events as $event)
    {!! Tracking::gtagCall($event) !!};
    @endforeach
</script>
@endif
@if ($enabled && $pixel)
<script>
    !function (f, b, e, v, n, t, s) { if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); }; if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = []; t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s); }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', {{ Js::from($pixel) }});
    fbq('track', 'PageView');
    @foreach ($events as $event)
    {!! Tracking::pixelCall($event) !!};
    @endforeach
</script>
@endif
