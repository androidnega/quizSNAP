@extends('layouts.dashboard')

@section('title', 'Add institution')
@section('dashboard_heading', 'Add institution')

@section('dashboard_content')
<div class="w-full space-y-6">
    <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
        <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('dashboard.institutions.index') }}" class="hover:text-primary-600">Institutions</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-900 font-medium">Add institution</span>
    </div>

    <p class="qs-page-intro">Create an institution, then add faculties and departments on the next screen. Assign coordinators to a faculty and examiners to a department via <a href="{{ route('dashboard.users.index') }}">User management</a>.</p>

    <div class="card max-w-2xl overflow-hidden">
        @if(session('error'))
            <div class="mx-6 mt-6 rounded-lg bg-danger-50 border border-danger-200 text-danger-800 px-4 py-3 text-sm">{{ session('error') }}</div>
        @endif

        <form action="{{ route('dashboard.institutions.store') }}" method="post" enctype="multipart/form-data" class="qs-form p-6">
            @csrf

            <x-form.section
                title="Institution details"
                description="Basic information shown across the dashboard and in staff assignment dropdowns."
            >
                <x-form.input
                    name="name"
                    label="Institution name"
                    required
                    placeholder="e.g. Accra Technical University"
                    hint="Official name as it should appear in reports and dropdowns."
                    full
                />

                <x-form.input
                    name="region"
                    label="Region"
                    optional
                    placeholder="e.g. Greater Accra Region"
                    hint="Optional. Shown next to the institution name when selecting from a list."
                    full
                />

                <x-form.file
                    name="logo"
                    label="Institution logo"
                    optional
                    accept="image/*"
                    hint="PNG or JPG, max 2MB. Displayed in the examiner sidebar."
                    full
                />
            </x-form.section>

            <x-form.actions
                submit="Create institution"
                :cancel="route('dashboard.institutions.index')"
            />
        </form>
    </div>
</div>
@endsection
