@extends('admin.monitoring.layout')
@php($pageTitle = 'Deployments')
@section('monitoring_content')
<form method="post" action="{{ route('dashboard.monitoring.deployments.store') }}" class="card qs-form max-w-xl mb-4">
    @csrf
    <div class="qs-section space-y-3">
        <label class="block text-sm font-medium">Deployment notes</label>
        <textarea name="notes" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></textarea>
        <button type="submit" class="btn btn-primary btn-sm">Record Current Deployment</button>
    </div>
</form>
@include('admin.monitoring.partials.log-table', ['rows' => $deployments, 'columns' => [
    ['key' => 'deployed_at', 'label' => 'When'],
    ['key' => 'version', 'label' => 'Version'],
    ['key' => 'git_commit', 'label' => 'Commit'],
    ['key' => 'branch', 'label' => 'Branch'],
    ['key' => 'deployed_by_name', 'label' => 'By'],
]])
@endsection
