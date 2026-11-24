@aware(['dialog' => null])
@props(['name' => 'slug', 'label' => 'Slug', 'source' => 'title', 'value' => null, 'auto' => false])
@php
    $fillAutomatically = $auto && ! (old('_modal') === $dialog && filled(old($name)));
@endphp
<x-field :name="$name" :label="$label" :value="$value" help="Terisi otomatis dari judul, bisa diubah." required autocapitalize="off" spellcheck="false" maxlength="120" x-data="slugField('{{ $source }}', {{ $fillAutomatically ? 'true' : 'false' }})" x-on:input="markManual()" x-on:blur="normalize()" />
