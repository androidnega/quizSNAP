{{--
  Show/hide password field for student auth flows.
  Required: $id
  Optional: $name, $autocomplete, $required, $class
--}}
@php
    $inputClass = $class ?? 'w-full px-3 py-2.5 pr-11 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500';
@endphp
<div class="relative">
    <input
        type="password"
        id="{{ $id }}"
        @if(!empty($name)) name="{{ $name }}" @endif
        @if(!empty($autocomplete)) autocomplete="{{ $autocomplete }}" @endif
        @if(!empty($required)) required @endif
        class="{{ $inputClass }}"
    >
    <button
        type="button"
        class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded-r-lg"
        data-password-toggle
        data-target="{{ $id }}"
        aria-label="Show password"
        aria-pressed="false"
        tabindex="-1"
    >
        <svg data-icon-show class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        </svg>
        <svg data-icon-hide class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
        </svg>
    </button>
</div>

@once
@push('scripts')
<script>
(function () {
    if (window.__quizsnapPasswordToggleBound) return;
    window.__quizsnapPasswordToggleBound = true;
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-password-toggle]');
        if (!btn) return;
        e.preventDefault();
        var input = document.getElementById(btn.getAttribute('data-target'));
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        var iconShow = btn.querySelector('[data-icon-show]');
        var iconHide = btn.querySelector('[data-icon-hide]');
        if (iconShow) iconShow.classList.toggle('hidden', show);
        if (iconHide) iconHide.classList.toggle('hidden', !show);
    });
})();
</script>
@endpush
@endonce
