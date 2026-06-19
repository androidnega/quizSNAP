@extends('layouts.dashboard')

@section('title', 'Add Academic Year')
@section('dashboard_heading')
<span class="inline-flex items-center gap-2"><i class="fas fa-calendar-alt text-primary-600"></i> Add Academic Year</span>
@endsection
@section('breadcrumb_trail')
<a href="{{ route('dashboard.coordinators.academic-years.index') }}" class="hover:text-primary-600">Academic Years</a>
<svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
<span class="text-gray-900 font-medium">Add</span>
@endsection

@section('dashboard_content')
<div class="w-full">
    @if(session('error'))
        <div class="alert alert-error mb-4">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error mb-4">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <a href="{{ route('dashboard.coordinators.academic-years.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-primary-600 mb-6">
        <i class="fas fa-arrow-left"></i> Back to Academic Years
    </a>

    <div class="w-full max-w-2xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <form action="{{ route('dashboard.coordinators.academic-years.store') }}" method="post" class="academic-year-form space-y-6">
            @csrf
            <div>
                <label for="year" class="block text-sm font-semibold text-gray-800 mb-2">Year <span class="font-normal text-gray-500">(e.g. 2024/2025)</span></label>
                <input type="text" name="year" id="year" value="{{ old('year') }}" required class="form-field-input" placeholder="2024/2025">
                @error('year')
                    <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex items-center gap-3 pt-2">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active') ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <span class="text-sm font-medium text-gray-700">Set as active year</span>
                </label>
            </div>
            <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">Create</button>
                <a href="{{ route('dashboard.coordinators.academic-years.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-gray-300">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
