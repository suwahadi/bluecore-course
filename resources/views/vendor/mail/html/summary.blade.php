@props(['rows'])
<table class="summary" width="100%" cellpadding="0" cellspacing="0" role="presentation">
@foreach ($rows as $row)
<tr class="{{ ($row[2] ?? false) ? 'summary-total' : 'summary-row' }}">
<td class="summary-label">{{ $row[0] }}</td>
<td class="summary-value" align="right">@if ($row[2] ?? false)<strong>{{ $row[1] }}</strong>@else{{ $row[1] }}@endif</td>
</tr>
@endforeach
</table>
