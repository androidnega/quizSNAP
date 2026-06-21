@extends('admin.monitoring.layout')
@php($pageTitle = 'User Sessions')
@section('monitoring_content')
@include('admin.monitoring.partials.log-table', ['rows' => $sessions, 'columns' => [
    ['key' => 'user_name', 'label' => 'User'],
    ['key' => 'user_role', 'label' => 'Role'],
    ['key' => 'ip_address', 'label' => 'IP'],
    ['key' => 'current_page', 'label' => 'Page'],
    ['key' => 'last_activity_at', 'label' => 'Last activity'],
]])
<div class="mt-4 rounded-xl border bg-white p-4 shadow-sm">
    <h3 class="text-sm font-semibold mb-3">Session Actions</h3>
    <form method="post" action="{{ route('dashboard.monitoring.sessions.terminate') }}" class="flex flex-wrap gap-2 mb-3">
        @csrf
        <input type="text" name="session_id" placeholder="Session ID" class="rounded-lg border border-gray-300 px-3 py-2 text-sm flex-1 min-w-[200px]" required>
        <button class="btn btn-danger btn-sm">Terminate Session</button>
    </form>
    <form method="post" action="{{ route('dashboard.monitoring.sessions.force-logout') }}" class="flex flex-wrap gap-2">
        @csrf
        <input type="number" name="user_id" placeholder="User ID" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
        <button class="btn btn-secondary btn-sm">Force Logout User</button>
    </form>
</div>
@endsection
