@props([
    'submit' => 'Save',
    'cancel' => null,
    'cancelLabel' => 'Cancel',
])

<div {{ $attributes->merge(['class' => 'qs-actions']) }}>
    <button type="submit" class="btn btn-primary">{{ $submit }}</button>
    @if($cancel)
        <a href="{{ $cancel }}" class="btn btn-secondary">{{ $cancelLabel }}</a>
    @endif
    {{ $slot }}
</div>
