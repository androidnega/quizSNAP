@php
    use App\Support\Favicon;

    $faviconUrl = Favicon::url();
    $faviconType = 'image/svg+xml';
@endphp
<link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
<link rel="apple-touch-icon" href="{{ $faviconUrl }}">
