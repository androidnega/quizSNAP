<div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50"><tr>
            <th class="px-4 py-3 text-left">UUID</th>
            <th class="px-4 py-3 text-left">Queue</th>
            <th class="px-4 py-3 text-left">Failed at</th>
            <th class="px-4 py-3 text-left">Actions</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($failedJobs as $job)
                <tr>
                    <td class="px-4 py-3 font-mono text-xs">{{ $job->uuid ?? $job->id ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $job->queue ?? 'default' }}</td>
                    <td class="px-4 py-3">{{ $job->failed_at ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <form method="post" action="{{ route('dashboard.monitoring.queue.retry') }}">@csrf<input type="hidden" name="uuid" value="{{ $job->uuid }}"><button class="btn btn-secondary btn-sm">Retry</button></form>
                            <form method="post" action="{{ route('dashboard.monitoring.queue.delete') }}">@csrf<input type="hidden" name="uuid" value="{{ $job->uuid }}"><button class="btn btn-danger btn-sm">Delete</button></form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No failed jobs.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if(method_exists($failedJobs, 'links'))<div class="mt-4">{{ $failedJobs->links() }}</div>@endif
