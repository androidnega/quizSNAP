@extends('layouts.dashboard')

@section('title', 'Message students')
@section('dashboard_heading', 'Message students')

@section('dashboard_content')
<div class="w-full max-w-2xl space-y-6">
    <p class="text-sm text-gray-600">Send an in-app notification to every student in a class group. Students see it on the bell icon in their mobile dashboard.</p>

    <form method="post" action="{{ route('dashboard.student-notifications.store') }}" class="rounded-lg border border-gray-200 bg-white p-5 space-y-4">
        @csrf
        <div>
            <label for="class_group_id" class="block text-sm font-medium text-gray-700 mb-1.5">Class group</label>
            <select name="class_group_id" id="class_group_id" required class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                <option value="">Select class group</option>
                @foreach($classGroups as $group)
                <option value="{{ $group->id }}" @selected(old('class_group_id') == $group->id)>{{ $group->name }}</option>
                @endforeach
            </select>
            @error('class_group_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700 mb-1.5">Title</label>
            <input type="text" name="title" id="title" value="{{ old('title') }}" maxlength="120" required placeholder="e.g. Midsem briefing tomorrow" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
            @error('title')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="body" class="block text-sm font-medium text-gray-700 mb-1.5">Message</label>
            <textarea name="body" id="body" rows="4" maxlength="2000" required placeholder="Write your message to students..." class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">{{ old('body') }}</textarea>
            @error('body')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center px-4 py-2.5 rounded-lg text-sm font-semibold text-gray-900 bg-yellow-400 hover:bg-yellow-500 border border-yellow-600/30">Send notification</button>
        </div>
    </form>

    <div class="rounded-lg border border-gray-200 bg-gray-50/80 p-4 text-sm text-gray-600 space-y-2">
        <p class="font-semibold text-gray-800">Automatic notifications</p>
        <ul class="list-disc pl-5 space-y-1">
            <li>New quiz published</li>
            <li>Exam timetable added or updated</li>
            <li>Result held for review</li>
            <li>Result released by examiner</li>
        </ul>
    </div>
</div>
@endsection
