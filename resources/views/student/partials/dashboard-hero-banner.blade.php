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
    $bundledSlug = 'student-dashboard-fathers-day-banner';
    $legacyBundledSlug = 'student-dashboard-midsem-exams-good-luck-banner';
    $usesBundledBanner = $mode === 'image' && (
        empty($image)
        || str_contains((string) $image, $bundledSlug)
        || str_contains((string) $image, $legacyBundledSlug)
    );
    $bundledBase = asset('images/' . $bundledSlug);
    $showBanner = ! empty($banner['enabled']) && (
        ($mode === 'image' && ($usesBundledBanner || ! empty($bannerImageUrl)))
        || ($mode === 'image_text')
    );
    $bannerAlt = 'Happy Father\'s Day. Thank you for your love, guidance, strength, and for being my greatest inspiration. Emmanuel Kofi Kwofie, Planning Committee Chair — FASSA.';
@endphp

@if($showBanner)
@if($mode === 'image' && ($usesBundledBanner || ! empty($bannerImageUrl)))
{{-- Wide hero banner (1024×395): responsive WebP with JPEG fallback --}}
<section aria-label="Dashboard banner" class="sd-hero-banner w-full min-w-0 h-full flex flex-col">
    <figure class="sd-hero-banner__media relative m-0 w-full flex-1 min-h-[168px] lg:min-h-[228px] overflow-hidden rounded-2xl lg:rounded-3xl bg-[#f8fafc] aspect-[1024/430] h-full">
        @if($usesBundledBanner)
        <picture>
            <source type="image/webp"
                    srcset="{{ $bundledBase }}-640.webp 640w, {{ $bundledBase }}.webp 1024w"
                    sizes="(max-width: 768px) 100vw, 100vw">
            <source type="image/jpeg"
                    srcset="{{ $bundledBase }}-640.jpg 640w, {{ $bundledBase }}.jpg 1024w"
                    sizes="(max-width: 768px) 100vw, 100vw">
            <img src="{{ $bundledBase }}.jpg"
                 alt="{{ $bannerAlt }}"
                 class="absolute inset-0 block h-full w-full object-cover object-center"
                 width="1024"
                 height="395"
                 loading="eager"
                 decoding="async"
                 fetchpriority="high">
        </picture>
        @else
        <img src="{{ e($bannerImageUrl) }}"
             alt="{{ $bannerAlt }}"
             class="absolute inset-0 block h-full w-full object-cover object-center"
             width="1024"
             height="395"
             loading="eager"
             decoding="async"
             fetchpriority="high">
        @endif
    </figure>
</section>
@elseif($mode === 'image_text')
{{-- Image + text: text left, image right --}}
<section aria-label="Dashboard banner" class="w-full min-w-0 h-full flex flex-col">
    <div class="overflow-hidden rounded-2xl lg:rounded-3xl border border-slate-200 bg-white shadow-[0_4px_24px_rgba(15,23,42,0.08)] h-full flex flex-col">
        <div class="grid grid-cols-1 lg:grid-cols-2 flex-1 lg:min-h-[200px]">
            <div class="flex flex-col justify-center px-5 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-6 order-2 lg:order-1">
                <h2 class="text-lg sm:text-xl lg:text-2xl xl:text-[1.65rem] font-extrabold text-slate-900 leading-snug tracking-tight">
                    {{ $banner['title'] }}
                    <span class="text-amber-500">{{ $banner['title_accent'] }}</span>
                </h2>
                @if(! empty(trim($banner['subtitle'] ?? '')))
                <p class="mt-1.5 lg:mt-2 text-xs sm:text-sm lg:text-base text-slate-600 max-w-md leading-relaxed">{{ $banner['subtitle'] }}</p>
                @endif
            </div>
            <div class="relative h-32 sm:h-36 lg:h-auto lg:min-h-[180px] xl:min-h-[200px] bg-slate-100 order-1 lg:order-2">
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
