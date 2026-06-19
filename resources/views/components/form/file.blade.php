@props([
    'label' => null,
    'name',
    'hint' => null,
    'accept' => null,
    'required' => false,
    'optional' => true,
    'full' => false,
    'previewUrl' => null,
    'previewAlt' => '',
])

@php
    $fieldId = $attributes->get('id') ?? $name;
    $hasError = $errors->has($name);
@endphp

<x-form.field :label="$label" :name="$name" :hint="$hint" :required="$required" :optional="$optional" :full="$full">
    <div class="qs-file" data-qs-file>
        <input
            type="file"
            name="{{ $name }}"
            id="{{ $fieldId }}"
            class="qs-file__input"
            @if($accept) accept="{{ $accept }}" @endif
            @if($required) required @endif
            @if($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
            {{ $attributes }}
        />
        <label for="{{ $fieldId }}" class="qs-file__drop">
            <span class="qs-file__icon" aria-hidden="true">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
            </span>
            <span class="qs-file__title">Click to upload or drag a file here</span>
            <span class="qs-file__subtitle" data-qs-file-hint>{{ $hint ?? 'Choose a file from your device' }}</span>
            <span class="qs-file__name hidden" data-qs-file-name></span>
        </label>
        @if($previewUrl)
            <div class="qs-file__preview" data-qs-file-existing>
                <img src="{{ $previewUrl }}" alt="{{ $previewAlt }}">
                <span class="text-xs text-gray-500">Current logo — upload a new file to replace</span>
            </div>
        @endif
    </div>
</x-form.field>

@once
    @push('scripts')
    <script>
    document.addEventListener('change', function(e) {
        var input = e.target;
        if (!input.matches('.qs-file__input')) return;
        var wrap = input.closest('[data-qs-file]');
        if (!wrap) return;
        var nameEl = wrap.querySelector('[data-qs-file-name]');
        var hintEl = wrap.querySelector('[data-qs-file-hint]');
        var existing = wrap.querySelector('[data-qs-file-existing]');
        if (input.files && input.files.length > 0) {
            if (nameEl) {
                nameEl.textContent = input.files[0].name;
                nameEl.classList.remove('hidden');
            }
            if (hintEl) hintEl.classList.add('hidden');
            if (existing) existing.classList.add('hidden');
        } else {
            if (nameEl) {
                nameEl.textContent = '';
                nameEl.classList.add('hidden');
            }
            if (hintEl) hintEl.classList.remove('hidden');
            if (existing) existing.classList.remove('hidden');
        }
    });
    </script>
    @endpush
@endonce
