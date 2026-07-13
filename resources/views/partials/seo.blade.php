@php
    use App\Support\BrandAssets;

    $appName = trim((string) \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name', 'QuizSnap'))) ?: 'QuizSnap';
    $seoTitle = trim($__env->yieldContent('title')) ?: $appName;
    if ($seoTitle !== $appName && ! str_contains($seoTitle, $appName)) {
        $seoTitleFull = $seoTitle.' · '.$appName;
    } else {
        $seoTitleFull = $seoTitle;
    }
    $seoDescription = trim($__env->yieldContent('meta_description')) ?: (
        $appName.' is a secure online quiz and examination platform with live proctoring, timed assessments, and instant results for schools and institutions.'
    );
    $seoRobots = trim($__env->yieldContent('robots')) ?: 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1';
    $canonical = url()->current();
    $ogImage = BrandAssets::ogImageUrl();
    $logoUrl = BrandAssets::logoUrl();
    $logoAlt = BrandAssets::logoAlt();
    $siteUrl = rtrim(config('app.url') ?: url('/'), '/');
@endphp
<meta name="description" content="{{ $seoDescription }}">
<meta name="robots" content="{{ $seoRobots }}">
<meta name="googlebot" content="{{ $seoRobots }}">
<meta name="author" content="{{ $appName }}">
<meta name="application-name" content="{{ $appName }}">
<meta name="keywords" content="QuizSnap, online quiz, online exam, proctored quiz, student assessment, e-learning, secure testing">
<link rel="canonical" href="{{ $canonical }}">

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $appName }}">
<meta property="og:title" content="{{ $seoTitleFull }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:alt" content="{{ $logoAlt }}">
<meta property="og:locale" content="en_US">

<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="{{ $seoTitleFull }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">
<meta name="twitter:image:alt" content="{{ $logoAlt }}">

<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Organization',
            '@id' => $siteUrl.'/#organization',
            'name' => $appName,
            'url' => $siteUrl.'/',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $logoUrl,
                'caption' => $logoAlt,
            ],
            'image' => $ogImage,
        ],
        [
            '@type' => 'WebSite',
            '@id' => $siteUrl.'/#website',
            'url' => $siteUrl.'/',
            'name' => $appName,
            'description' => $seoDescription,
            'publisher' => ['@id' => $siteUrl.'/#organization'],
            'inLanguage' => 'en',
        ],
        [
            '@type' => 'WebPage',
            '@id' => $canonical.'#webpage',
            'url' => $canonical,
            'name' => $seoTitleFull,
            'description' => $seoDescription,
            'isPartOf' => ['@id' => $siteUrl.'/#website'],
            'about' => ['@id' => $siteUrl.'/#organization'],
            'primaryImageOfPage' => [
                '@type' => 'ImageObject',
                'url' => $ogImage,
            ],
            'inLanguage' => 'en',
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}
</script>
