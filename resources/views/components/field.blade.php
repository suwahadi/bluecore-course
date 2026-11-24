@aware(['dialog' => null])
@props(['name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'required' => false, 'id' => null, 'full' => false, 'bag' => 'default', 'options' => null])
@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $fieldId = $id ?? ($dialog ? $dialog.'-' : '').str_replace('.', '-', $key);
    $errorBag = $dialog ?? $bag;
    $error = isset($errors) ? $errors->getBag($errorBag)->first($key) : null;
    $current = old('_modal') === $dialog ? old($key, $value) : $value;
    $current = is_bool($current) ? (int) $current : $current;
    $describedBy = trim(($help ? $fieldId.'-help ' : '').($error ? $fieldId.'-error' : ''));
@endphp
<div class="{{ $full ? 'field field--full' : 'field' }}">
    <label for="{{ $fieldId }}">{{ $label }}</label>
    @if ($type === 'textarea')
        <textarea id="{{ $fieldId }}" name="{{ $name }}" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error) aria-invalid="true" @endif @required($required) {{ $attributes }}>{{ $current }}</textarea>
    @elseif ($type === 'select')
        <select id="{{ $fieldId }}" name="{{ $name }}" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error) aria-invalid="true" @endif @required($required) {{ $attributes }}>
            @if ($options === null)
                {{ $slot }}
            @else
                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $optionValue === (string) $current)>{{ $optionLabel }}</option>
                @endforeach
            @endif
        </select>
    @else
        <input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : $current }}" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error) aria-invalid="true" @endif @required($required) {{ $attributes }}>
    @endif
    @if ($help)
        <span class="field__help" id="{{ $fieldId }}-help">{{ $help }}</span>
    @endif
    @if ($error)
        <span class="field__error" id="{{ $fieldId }}-error" role="alert">{{ $error }}</span>
    @endif
</div>
