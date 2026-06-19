@props([
    'label' => null,
    'name',
    'hint' => null,
    'placeholder' => '— Select —',
    'required' => false,
    'optional' => false,
    'full' => false,
    'disabled' => false,
    'options' => [],
    'selected' => null,
])

@php
    $fieldId = $attributes->get('id') ?? $name;
    $resolvedSelected = old($name, $selected);
    $hasError = $errors->has($name);
    $controlClass = 'qs-control' . ($hasError ? ' qs-control--error' : '');
@endphp

<x-form.field :label="$label" :name="$name" :hint="$hint" :required="$required" :optional="$optional" :full="$full">
    <select
        name="{{ $name }}"
        id="{{ $fieldId }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
        {{ $attributes->merge(['class' => $controlClass]) }}
    >
        @if($placeholder !== false)
            <option value="">{{ $placeholder }}</option>
        @endif
        @if($slot->isNotEmpty())
            {{ $slot }}
        @else
            @foreach($options as $optValue => $optLabel)
                <option value="{{ $optValue }}" @selected((string) $resolvedSelected === (string) $optValue)>{{ $optLabel }}</option>
            @endforeach
        @endif
    </select>
</x-form.field>
