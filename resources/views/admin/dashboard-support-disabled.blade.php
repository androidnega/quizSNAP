@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('dashboard_heading', 'Dashboard')

@section('dashboard_content')
<div class="mx-auto max-w-lg rounded-xl border border-slate-200 bg-white px-6 py-10 text-center">
    <h2 class="text-lg font-semibold text-slate-900">Live chat is unavailable</h2>
    <p class="mt-2 text-sm text-slate-500">Support chat has been turned off for all roles. Contact your system administrator if you need access restored.</p>
</div>
@endsection
