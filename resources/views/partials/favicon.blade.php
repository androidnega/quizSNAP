@php
    use App\Support\Favicon;
@endphp
<link rel="icon" href="{{ Favicon::url() }}" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="{{ Favicon::png32() }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ Favicon::png16() }}">
<link rel="apple-touch-icon" href="{{ Favicon::appleTouchIcon() }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">
