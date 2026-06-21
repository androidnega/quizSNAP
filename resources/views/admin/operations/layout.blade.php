@extends('layouts.dashboard')

@section('title', $pageTitle ?? 'Operations Center')
@section('dashboard_heading', $pageTitle ?? 'Operations Center')

@section('dashboard_content')
<div class="w-full min-w-0 space-y-4" data-operations-page="{{ $operationsPage ?? 'command-center' }}">
    @include('admin.operations.partials.nav')

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    @yield('operations_content')
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/quizsnap-operations.js') }}" defer></script>
@endpush
