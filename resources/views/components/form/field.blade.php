@props([
    'label' => null,
    'name' => null,
    'hint' => null,
    'required' => false,
    'optional' => false,
    'full' => false,
])

@php
    $fieldId = $attributes->get('id') ?? ($name ? str_replace(['[', ']'], ['_', ''], $name) : null);
    $hasError = $name && $errors->has($name);
@endphp

<div {{ $attributes->merge(['class' => 'qs-field' . ($full ? ' qs-field--full' : '')]) }}>
    @if($label)
        <label @if($fieldId) for="{{ $fieldId }}" @endif class="qs-label">
            <span>{{ $label }}</span>
            @if($required)
                <span class="qs-label__required" aria-hidden="true">*</span>
            @elseif($optional)
                <span class="qs-label__optional">(optional)</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if($hint)
        <p class="qs-hint">{{ $hint }}</p>
    @endif

    @if($hasError)
        <p class="qs-error" id="{{ $fieldId }}-error">{{ $errors->first($name) }}</p>
    @endif
</div>
