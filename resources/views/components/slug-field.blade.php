@props(['name' => 'slug', 'label' => 'Slug', 'source' => 'title', 'value' => null, 'auto' => false])
<x-field :name="$name" :label="$label" :value="$value" help="Terisi otomatis dari judul, bisa diubah." required autocapitalize="off" spellcheck="false" maxlength="120" x-data="slugField('{{ $source }}', {{ $auto ? 'true' : 'false' }})" x-on:input="markManual()" x-on:blur="normalize()" />
