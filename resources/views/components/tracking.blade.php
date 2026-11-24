@php
    $ga4 = \App\Support\Settings::get('ga4_measurement_id');
    $pixel = \App\Support\Settings::get('meta_pixel_id');
    $enabled = ($ga4 || $pixel) && ! request()->routeIs('admin.*') && ! request()->is('admin', 'admin/*') && ! auth()->user()?->isAdmin();
    $events = $enabled ? array_values(array_filter((array) session('tracking_events', []), 'is_string')) : [];
@endphp
@if ($enabled && $ga4)
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4 }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', {{ Js::from($ga4) }});
    @if (in_array('sign_up', $events, true))
    gtag('event', 'sign_up');
    @endif
</script>
@endif
@if ($enabled && $pixel)
<script>
    !function (f, b, e, v, n, t, s) { if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); }; if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = []; t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s); }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', {{ Js::from($pixel) }});
    fbq('track', 'PageView');
    @if (in_array('sign_up', $events, true))
    fbq('track', 'CompleteRegistration');
    @endif
</script>
@endif
