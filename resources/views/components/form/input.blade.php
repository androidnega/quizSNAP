@props([
    'label' => null,
    'name',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'placeholder' => null,
    'required' => false,
    'optional' => false,
    'full' => false,
    'readonly' => false,
    'disabled' => false,
])

@php
    $fieldId = $attributes->get('id') ?? $name;
    $resolvedValue = old($name, $value);
    $hasError = $errors->has($name);
    $controlClass = 'qs-control' . ($hasError ? ' qs-control--error' : '');
@endphp

<x-form.field :label="$label" :name="$name" :hint="$hint" :required="$required" :optional="$optional" :full="$full">
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $fieldId }}"
        value="{{ $type !== 'password' ? $resolvedValue : '' }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($required) required @endif
        @if($readonly) readonly @endif
        @if($disabled) disabled @endif
        @if($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
        {{ $attributes->merge(['class' => $controlClass]) }}
    />
</x-form.field>
