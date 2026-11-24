@props(['name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'required' => false, 'id' => null, 'full' => false, 'bag' => 'default'])
@php
    $fieldId = $id ?? $name;
    $error = isset($errors) ? $errors->getBag($bag)->first($name) : null;
    $describedBy = trim(($help ? $fieldId.'-help ' : '').($error ? $fieldId.'-error' : ''));
@endphp
<div class="{{ $full ? 'field field--full' : 'field' }}">
    <label for="{{ $fieldId }}">{{ $label }}</label>
    @if ($type === 'textarea')
        <textarea id="{{ $fieldId }}" name="{{ $name }}" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error) aria-invalid="true" @endif @required($required) {{ $attributes }}>{{ old($name, $value) }}</textarea>
    @elseif ($type === 'select')
        <select id="{{ $fieldId }}" name="{{ $name }}" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error) aria-invalid="true" @endif @required($required) {{ $attributes }}>{{ $slot }}</select>
    @else
        <input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : old($name, $value) }}" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error) aria-invalid="true" @endif @required($required) {{ $attributes }}>
    @endif
    @if ($help)
        <span class="field__help" id="{{ $fieldId }}-help">{{ $help }}</span>
    @endif
    @if ($error)
        <span class="field__error" id="{{ $fieldId }}-error" role="alert">{{ $error }}</span>
    @endif
</div>
