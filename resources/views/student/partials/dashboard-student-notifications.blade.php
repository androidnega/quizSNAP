@php
    $studentNotificationUnread = (int) ($studentNotificationUnread ?? 0);
    $studentNotifications = $studentNotifications ?? collect();
@endphp

<div class="stu-notif" data-stu-notif>
    <button type="button"
            class="stu-notif__bell"
            aria-label="Notifications{{ $studentNotificationUnread > 0 ? ' ('.$studentNotificationUnread.' unread)' : '' }}"
            aria-expanded="false"
            aria-controls="stu-notif-panel"
            data-stu-notif-toggle>
        <i class="fas fa-bell" aria-hidden="true"></i>
        @if($studentNotificationUnread > 0)
        <span class="stu-notif__badge" data-stu-notif-badge>{{ $studentNotificationUnread > 99 ? '99+' : $studentNotificationUnread }}</span>
        @else
        <span class="stu-notif__badge stu-notif__badge--hidden" data-stu-notif-badge hidden></span>
        @endif
    </button>

    <div id="stu-notif-panel" class="stu-notif__panel" hidden data-stu-notif-panel role="dialog" aria-label="Notifications">
        <div class="stu-notif__panel-head">
            <h2 class="stu-notif__panel-title">Notifications</h2>
            @if($studentNotificationUnread > 0)
            <button type="button" class="stu-notif__mark-all" data-stu-notif-mark-all>Mark all read</button>
            @endif
        </div>
        <ul class="stu-notif__list" data-stu-notif-list>
            @forelse($studentNotifications as $notification)
            <li class="stu-notif__item {{ $notification->isRead() ? 'is-read' : 'is-unread' }}" data-stu-notif-item data-id="{{ $notification->id }}">
                @if($notification->action_url)
                <a href="{{ $notification->action_url }}" class="stu-notif__link" data-stu-notif-link>
                @else
                <div class="stu-notif__link">
                @endif
                    <span class="stu-notif__icon" aria-hidden="true"><i class="fas {{ $notification->iconClass() }}"></i></span>
                    <span class="stu-notif__copy">
                        <span class="stu-notif__item-title">{{ $notification->title }}</span>
                        @if($notification->body)
                        <span class="stu-notif__item-body">{{ $notification->body }}</span>
                        @endif
                        <span class="stu-notif__time">{{ $notification->created_at?->diffForHumans() }}</span>
                    </span>
                @if($notification->action_url)
                </a>
                @else
                </div>
                @endif
            </li>
            @empty
            <li class="stu-notif__empty">No notifications yet.</li>
            @endforelse
        </ul>
    </div>
    <div class="stu-notif__backdrop" hidden data-stu-notif-backdrop></div>
</div>

@once
@push('styles')
<style>
    .stu-notif {
        position: relative;
    }

    .stu-notif__bell {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.75rem;
        border: 1px solid var(--theme-border);
        background: var(--theme-surface);
        color: var(--theme-text);
        cursor: pointer;
        transition: background 0.15s ease, border-color 0.15s ease;
    }

    .stu-notif__bell:active {
        background: var(--theme-primary-50);
        border-color: var(--theme-primary-200);
    }

    .stu-notif__badge {
        position: absolute;
        top: -0.3125rem;
        right: -0.3125rem;
        min-width: 1.125rem;
        height: 1.125rem;
        padding: 0 0.3125rem;
        border-radius: 9999px;
        background: #ef4444;
        color: #fff;
        font-size: 0.5625rem;
        font-weight: 800;
        line-height: 1.125rem;
        text-align: center;
        border: 2px solid var(--theme-surface);
    }

    .stu-notif__badge--hidden {
        display: none;
    }

    .stu-notif__backdrop {
        position: fixed;
        inset: 0;
        z-index: 48;
        background: rgba(15, 23, 42, 0.28);
    }

    .stu-notif__panel {
        position: fixed;
        top: max(3.25rem, calc(env(safe-area-inset-top) + 2.75rem));
        right: max(0.75rem, env(safe-area-inset-right));
        left: max(0.75rem, env(safe-area-inset-left));
        z-index: 49;
        max-height: min(28rem, calc(100vh - 5rem));
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-radius: 1rem;
        border: 1px solid var(--theme-border);
        background: var(--theme-surface);
    }

    @media (min-width: 480px) {
        .stu-notif__panel {
            left: auto;
            width: min(22rem, calc(100vw - 1.5rem));
        }
    }

    .stu-notif__panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.875rem 1rem;
        border-bottom: 1px solid var(--theme-border);
    }

    .stu-notif__panel-title {
        margin: 0;
        font-size: 0.9375rem;
        font-weight: 800;
        color: var(--theme-text);
    }

    .stu-notif__mark-all {
        border: none;
        background: none;
        padding: 0;
        font-size: 0.6875rem;
        font-weight: 700;
        color: var(--theme-primary-600);
        cursor: pointer;
    }

    .stu-notif__list {
        list-style: none;
        margin: 0;
        padding: 0.375rem 0;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }

    .stu-notif__item.is-unread {
        background: var(--theme-primary-50);
    }

    .stu-notif__link {
        display: flex;
        align-items: flex-start;
        gap: 0.625rem;
        padding: 0.6875rem 1rem;
        text-decoration: none;
        color: inherit;
    }

    .stu-notif__icon {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.875rem;
        height: 1.875rem;
        border-radius: 0.5rem;
        background: var(--theme-bg);
        color: var(--theme-primary-600);
        font-size: 0.75rem;
    }

    .stu-notif__copy {
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
        min-width: 0;
    }

    .stu-notif__item-title {
        font-size: 0.8125rem;
        font-weight: 700;
        line-height: 1.35;
        color: var(--theme-text);
    }

    .stu-notif__item-body {
        font-size: 0.6875rem;
        line-height: 1.4;
        color: var(--theme-muted);
    }

    .stu-notif__time {
        font-size: 0.625rem;
        color: var(--theme-muted);
    }

    .stu-notif__empty {
        padding: 1.5rem 1rem;
        text-align: center;
        font-size: 0.8125rem;
        color: var(--theme-muted);
    }
</style>
@endpush

@push('scripts')
<script>
(function() {
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var token = csrf ? csrf.getAttribute('content') : '';

    function setOpen(wrap, open) {
        var panel = wrap.querySelector('[data-stu-notif-panel]');
        var backdrop = wrap.querySelector('[data-stu-notif-backdrop]');
        var toggle = wrap.querySelector('[data-stu-notif-toggle]');
        if (!panel || !backdrop || !toggle) return;
        panel.hidden = !open;
        backdrop.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function updateBadge(wrap, count) {
        var badge = wrap.querySelector('[data-stu-notif-badge]');
        if (!badge) return;
        if (count > 0) {
            badge.hidden = false;
            badge.classList.remove('stu-notif__badge--hidden');
            badge.textContent = count > 99 ? '99+' : String(count);
        } else {
            badge.hidden = true;
            badge.classList.add('stu-notif__badge--hidden');
        }
        var toggle = wrap.querySelector('[data-stu-notif-toggle]');
        if (toggle) {
            toggle.setAttribute('aria-label', count > 0 ? 'Notifications (' + count + ' unread)' : 'Notifications');
        }
    }

    function markRead(wrap, id) {
        fetch('/dashboard/notifications/' + id + '/read', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (typeof data.unread_count === 'number') {
                updateBadge(wrap, data.unread_count);
            }
            var item = wrap.querySelector('[data-stu-notif-item][data-id="' + id + '"]');
            if (item) {
                item.classList.remove('is-unread');
                item.classList.add('is-read');
            }
        }).catch(function() {});
    }

    document.querySelectorAll('[data-stu-notif]').forEach(function(wrap) {
        var toggle = wrap.querySelector('[data-stu-notif-toggle]');
        var backdrop = wrap.querySelector('[data-stu-notif-backdrop]');
        var markAll = wrap.querySelector('[data-stu-notif-mark-all]');

        if (toggle) {
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                var panel = wrap.querySelector('[data-stu-notif-panel]');
                setOpen(wrap, panel && panel.hidden);
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', function() { setOpen(wrap, false); });
        }

        wrap.querySelectorAll('[data-stu-notif-link]').forEach(function(link) {
            link.addEventListener('click', function() {
                var item = link.closest('[data-stu-notif-item]');
                if (item && item.classList.contains('is-unread')) {
                    markRead(wrap, item.getAttribute('data-id'));
                }
                setOpen(wrap, false);
            });
        });

        if (markAll) {
            markAll.addEventListener('click', function() {
                fetch('/dashboard/notifications/read-all', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                }).then(function(r) { return r.json(); }).then(function() {
                    updateBadge(wrap, 0);
                    wrap.querySelectorAll('[data-stu-notif-item].is-unread').forEach(function(item) {
                        item.classList.remove('is-unread');
                        item.classList.add('is-read');
                    });
                    if (markAll) markAll.remove();
                }).catch(function() {});
            });
        }
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('[data-stu-notif]')) {
            document.querySelectorAll('[data-stu-notif]').forEach(function(wrap) {
                setOpen(wrap, false);
            });
        }
    });
})();
</script>
@endpush
@endonce
