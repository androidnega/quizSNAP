@props([
    'title' => null,
    'description' => null,
    'columns' => null,
])

@php
    $gridClass = 'qs-section__grid';
    if ($columns === 2) {
        $gridClass .= ' qs-section__grid--2';
    }
@endphp

<section {{ $attributes->merge(['class' => 'qs-section']) }}>
    @if($title || $description)
        <header class="qs-section__header">
            @if($title)
                <h2 class="qs-section__title">{{ $title }}</h2>
            @endif
            @if($description)
                <p class="qs-section__desc">{{ $description }}</p>
            @endif
        </header>
    @endif
    <div class="{{ $gridClass }}">
        {{ $slot }}
    </div>
</section>
