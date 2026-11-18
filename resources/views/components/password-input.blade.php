@props(['name', 'label', 'help' => null, 'autocomplete' => 'current-password', 'required' => false, 'id' => null])
@php
    $fieldId = $id ?? $name;
    $error = isset($errors) ? $errors->first($name) : null;
    $describedBy = trim(($help ? $fieldId.'-help ' : '').($error ? $fieldId.'-error' : ''));
@endphp
<div class="field">
    <label for="{{ $fieldId }}">{{ $label }}</label>
    <span class="password-field" x-data="{ visible: false }">
        <input id="{{ $fieldId }}" name="{{ $name }}" type="password" x-bind:type="visible ? 'text' : 'password'" autocomplete="{{ $autocomplete }}" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error) aria-invalid="true" @endif @required($required) {{ $attributes }}>
        <button type="button" class="password-toggle" aria-pressed="false" x-bind:aria-pressed="visible.toString()" aria-label="Tampilkan kata sandi" x-bind:aria-label="visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" x-on:click="visible = ! visible">
            <x-icon name="eye" x-show="! visible" />
            <x-icon name="eyeOff" x-show="visible" x-cloak />
        </button>
    </span>
    @if ($help)
        <span class="field__help" id="{{ $fieldId }}-help">{{ $help }}</span>
    @endif
    @if ($error)
        <span class="field__error" id="{{ $fieldId }}-error" role="alert">{{ $error }}</span>
    @endif
</div>
