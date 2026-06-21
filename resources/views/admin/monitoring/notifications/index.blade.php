@extends('admin.monitoring.layout')
@php($pageTitle = 'Notifications')
@section('monitoring_content')
<div class="flex justify-between mb-4">
    <p class="text-sm text-gray-600">{{ $unreadCount }} unread</p>
    <form method="post" action="{{ route('dashboard.monitoring.notifications.read-all') }}">@csrf<button class="btn btn-secondary btn-sm">Mark all read</button></form>
</div>
<div id="monitoring-notifications-list" class="space-y-2">
    @foreach($notifications as $notification)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm {{ $notification->read_at ? 'opacity-70' : '' }}">
            <div class="flex justify-between gap-2">
                <span class="text-xs font-semibold uppercase">{{ $notification->severity }}</span>
                <span class="text-xs text-gray-500">{{ $notification->created_at }}</span>
            </div>
            <p class="font-semibold text-gray-900 mt-1">{{ $notification->title }}</p>
            <p class="text-sm text-gray-600">{{ $notification->message }}</p>
        </div>
    @endforeach
</div>
@endsection
