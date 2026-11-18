@props(['status'])
@php
    $tone = match ($status) {
        \App\Enums\OrderStatus::Pending => 'badge--warn',
        \App\Enums\OrderStatus::Paid => 'badge--ok',
        \App\Enums\OrderStatus::Failed => 'badge--bad',
        default => '',
    };
@endphp
<span class="{{ trim('badge '.$tone) }}">{{ $status->label() }}</span>
