@php
    $banner = $dashboardBanner ?? \App\Models\Setting::getStudentDashboardBannerConfig();
    $mode = $banner['mode'] ?? 'image';
    $image = $banner['image'] ?? null;
    if (empty($image) && ! empty($banner['images'][0] ?? null)) {
        $image = $banner['images'][0];
    }
    if (empty($image)) {
        $fallback = trim((string) \App\Models\Setting::getValue(\App\Models\Setting::KEY_LOGIN_HERO_IMAGE, ''));
        if ($fallback !== '' && $mode === 'image_text') {
            $image = $fallback;
        }
    }
    $bannerImageUrl = $image;
    if (is_string($bannerImageUrl) && $bannerImageUrl !== '' && ! preg_match('#^https?://#i', $bannerImageUrl)) {
        $bannerImageUrl = asset(ltrim($bannerImageUrl, '/'));
    }
    $bundledSlug = 'student-dashboard-midsem-exams-good-luck-banner';
    $usesBundledBanner = $mode === 'image' && (
        empty($image)
        || str_contains((string) $image, $bundledSlug)
    );
    $bundledBase = asset('images/' . $bundledSlug);
    $showBanner = ! empty($banner['enabled']) && (
        ($mode === 'image' && ($usesBundledBanner || ! empty($bannerImageUrl)))
        || ($mode === 'image_text')
    );
    $bannerAlt = 'Good luck in your midsem exams. Believe in yourself, stay focused, and do your best. Emmanuel Kofi Kwofie, Planning Committee Chair — FASSA.';
@endphp

@if($showBanner)
@if($mode === 'image' && ($usesBundledBanner || ! empty($bannerImageUrl)))
{{-- Wide hero banner (1024×394): responsive WebP with JPEG fallback --}}
<section aria-label="Dashboard banner" class="w-full min-w-0">
    <figure class="relative m-0 w-full min-w-0 overflow-hidden rounded-2xl lg:rounded-3xl bg-[#fef9e7] aspect-[999/291] xl:aspect-[999/220]">
        @if($usesBundledBanner)
        <picture>
            <source type="image/webp"
                    srcset="{{ $bundledBase }}-640.webp 640w, {{ $bundledBase }}.webp 1024w"
                    sizes="(max-width: 768px) 100vw, 1024px">
            <source type="image/jpeg"
                    srcset="{{ $bundledBase }}-640.jpg 640w, {{ $bundledBase }}.jpg 1024w"
                    sizes="(max-width: 768px) 100vw, 1024px">
            <img src="{{ $bundledBase }}.jpg"
                 alt="{{ $bannerAlt }}"
                 class="absolute inset-0 block h-full w-full object-cover object-center"
                 width="999"
                 height="291"
                 loading="eager"
                 decoding="async"
                 fetchpriority="high">
        </picture>
        @else
        <img src="{{ e($bannerImageUrl) }}"
             alt="{{ $bannerAlt }}"
             class="absolute inset-0 block h-full w-full object-cover object-center"
             width="999"
             height="291"
             loading="eager"
             decoding="async"
             fetchpriority="high">
        @endif
    </figure>
</section>
@elseif($mode === 'image_text')
{{-- Image + text: text left, image right --}}
<section aria-label="Dashboard banner" class="w-full min-w-0">
    <div class="overflow-hidden rounded-2xl lg:rounded-3xl border border-slate-200 bg-white shadow-[0_4px_24px_rgba(15,23,42,0.08)]">
        <div class="grid grid-cols-1 lg:grid-cols-2 lg:min-h-[152px] xl:min-h-[168px]">
            <div class="flex flex-col justify-center px-5 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-6 order-2 lg:order-1">
                <h2 class="text-lg sm:text-xl lg:text-2xl xl:text-[1.65rem] font-extrabold text-slate-900 leading-snug tracking-tight">
                    {{ $banner['title'] }}
                    <span class="text-amber-500">{{ $banner['title_accent'] }}</span>
                </h2>
                @if(! empty(trim($banner['subtitle'] ?? '')))
                <p class="mt-1.5 lg:mt-2 text-xs sm:text-sm lg:text-base text-slate-600 max-w-md leading-relaxed">{{ $banner['subtitle'] }}</p>
                @endif
            </div>
            <div class="relative h-28 sm:h-32 lg:h-auto lg:min-h-[152px] xl:min-h-[168px] bg-slate-100 order-1 lg:order-2">
                @if(! empty($bannerImageUrl))
                <img src="{{ e($bannerImageUrl) }}"
                     alt=""
                     class="absolute inset-0 w-full h-full object-cover object-center"
                     loading="eager"
                     referrerpolicy="no-referrer">
                @else
                <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200">
                    <i class="fas fa-image text-3xl lg:text-4xl text-slate-300" aria-hidden="true"></i>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endif
@endif
