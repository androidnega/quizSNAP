@props([
    'label' => null,
    'name',
    'value' => null,
    'hint' => null,
    'placeholder' => null,
    'required' => false,
    'optional' => false,
    'full' => false,
    'rows' => 4,
])

@php
    $fieldId = $attributes->get('id') ?? $name;
    $resolvedValue = old($name, $value);
    $hasError = $errors->has($name);
    $controlClass = 'qs-control' . ($hasError ? ' qs-control--error' : '');
@endphp

<x-form.field :label="$label" :name="$name" :hint="$hint" :required="$required" :optional="$optional" :full="$full">
    <textarea
        name="{{ $name }}"
        id="{{ $fieldId }}"
        rows="{{ $rows }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($required) required @endif
        @if($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
        {{ $attributes->merge(['class' => $controlClass]) }}
    >{{ $resolvedValue }}</textarea>
</x-form.field>
