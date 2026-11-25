@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" class="header-link">
<img src="{{ \App\Support\SiteLogo::url() }}" class="logo" width="60" height="36" alt="">
<span class="header-name">{{ $slot }}</span>
</a>
</td>
</tr>
