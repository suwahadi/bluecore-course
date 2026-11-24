@aware(['dialog' => null])
@props(['name', 'label', 'checked' => false, 'full' => true])
@php
    $isChecked = old('_modal') === $dialog && session()->hasOldInput() ? (bool) old($name) : (bool) $checked;
@endphp
<label class="{{ $full ? 'field field--inline field--full' : 'field field--inline' }}">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="1" @checked($isChecked) {{ $attributes }}>
    <span>{{ $label }}</span>
</label>
