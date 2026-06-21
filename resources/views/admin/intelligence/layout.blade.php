@extends('layouts.dashboard')

@section('title', $pageTitle ?? 'Intelligence Center')
@section('dashboard_heading', $pageTitle ?? 'Intelligence Center')

@section('dashboard_content')
<div class="w-full min-w-0 space-y-4" data-intelligence-page="{{ $intelligencePage ?? 'executive' }}">
    @include('admin.intelligence.partials.nav')
    @if(session('success'))<div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    @yield('intelligence_content')
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/quizsnap-intelligence.js') }}" defer></script>
@endpush
