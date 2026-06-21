@php
    $monitoringUser = auth()->user();
    $monitoringUnread = $canAccessMonitoring ?? false
        ? app(\App\Services\Monitoring\MonitoringNotificationService::class)->unreadCount($monitoringUser)
        : 0;
    $monitoringRecent = ($canAccessMonitoring ?? false)
        ? app(\App\Services\Monitoring\MonitoringNotificationService::class)->recent(8, $monitoringUser)
        : collect();
@endphp
@if($canAccessMonitoring ?? false)
<div class="relative flex flex-shrink-0 items-center" id="monitoring-notification-wrap">
    <button type="button" id="monitoring-notification-btn" class="relative flex h-11 w-11 items-center justify-center rounded-lg text-gray-700 hover:bg-gray-100" aria-label="Monitoring notifications">
        <i class="fas fa-bell text-lg"></i>
        <span id="monitoring-notification-badge" class="absolute -top-0.5 -right-0.5 min-w-[1.1rem] rounded-full bg-red-600 px-1 text-[10px] font-bold leading-4 text-white text-center {{ $monitoringUnread ? '' : 'hidden' }}">{{ $monitoringUnread }}</span>
    </button>
    <div id="monitoring-notification-dropdown" class="absolute right-0 top-full z-[120] mt-1.5 hidden w-80 max-w-[calc(100vw-1rem)] rounded-xl border border-gray-200 bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2">
            <span class="text-sm font-semibold text-gray-900">Monitoring Alerts</span>
            <a href="{{ route('dashboard.monitoring.notifications.index') }}" class="text-xs text-primary-600 hover:underline">View all</a>
        </div>
        <div id="monitoring-notification-list" class="max-h-80 overflow-y-auto divide-y divide-gray-100">
            @forelse($monitoringRecent as $notification)
                <div class="px-3 py-2.5 text-sm {{ $notification->read_at ? 'opacity-70' : '' }}" data-notification-id="{{ $notification->id }}">
                    <div class="flex items-center justify-between gap-2">
                        @php
                            $badgeClass = match($notification->severity) {
                                'critical', 'fatal' => 'bg-red-100 text-red-700',
                                'warning' => 'bg-amber-100 text-amber-700',
                                'success' => 'bg-green-100 text-green-700',
                                default => 'bg-blue-100 text-blue-700',
                            };
                        @endphp
                        <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase {{ $badgeClass }}">{{ $notification->severity }}</span>
                        <span class="text-[10px] text-gray-500">{{ $notification->created_at?->diffForHumans() }}</span>
                    </div>
                    <p class="mt-1 font-medium text-gray-900">{{ $notification->title }}</p>
                    <p class="text-xs text-gray-600 line-clamp-2">{{ $notification->message }}</p>
                </div>
            @empty
                <p class="px-3 py-6 text-center text-sm text-gray-500">No alerts</p>
            @endforelse
        </div>
        <div class="flex gap-2 border-t border-gray-100 px-3 py-2">
            <form method="post" action="{{ route('dashboard.monitoring.notifications.read-all') }}" class="flex-1">@csrf<button type="submit" class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-xs font-medium hover:bg-gray-50">Mark all read</button></form>
            <a href="{{ route('dashboard.monitoring.notifications.index') }}" class="flex-1 rounded-lg bg-primary-600 px-2 py-1.5 text-center text-xs font-medium text-white hover:bg-primary-700">Open center</a>
        </div>
    </div>
</div>
@endif
