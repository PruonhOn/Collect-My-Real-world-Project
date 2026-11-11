@extends('layouts.admin')

@section('title', 'Notifications')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Notifications</li>
@endsection

@section('page-title', 'Notifications')
@section('page-subtitle', 'Stay updated with system activities')

@section('page-actions')
    @php $unreadCount = $unreadCount ?? $notifications->where('read_at', null)->count() ?? 0; @endphp
    @if($unreadCount > 0)
        <form method="POST" action="{{ route('notifications.mark-all-as-read') }}" class="d-inline" id="markAllReadForm">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-check-all me-1"></i> Mark All as Read
            </button>
        </form>
    @endif
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <span class="fw-bold">
                <i class="bi bi-bell me-1"></i>
                All Notifications
            </span>
            @if($unreadCount > 0)
                <span class="badge bg-danger rounded-pill ms-2">{{ $unreadCount }} unread</span>
            @endif
        </div>
        <div class="d-flex gap-2">
            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary {{ !request('filter') || request('filter') === 'all' ? 'active' : '' }}">All</a>
                <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="btn btn-outline-secondary {{ request('filter') === 'unread' ? 'active' : '' }}">Unread</a>
                <a href="{{ route('notifications.index', ['filter' => 'read']) }}" class="btn btn-outline-secondary {{ request('filter') === 'read' ? 'active' : '' }}">Read</a>
            </div>
        </div>
    </div>

    <div class="list-group list-group-flush">
        @forelse(($groupedNotifications ?? $notifications ?? []) as $date => $group)
            <div class="list-group-item bg-light bg-opacity-50 py-2 px-4">
                <small class="text-muted fw-semibold">
                    <i class="bi bi-calendar3 me-1"></i>
                    {{ $date instanceof \Carbon\Carbon ? $date->format('l, F d, Y') : $date }}
                    @if($date instanceof \Carbon\Carbon && $date->isToday())
                        <span class="badge bg-primary ms-1">Today</span>
                    @elseif($date instanceof \Carbon\Carbon && $date->isYesterday())
                        <span class="badge bg-secondary ms-1">Yesterday</span>
                    @endif
                </small>
            </div>
            @foreach($group as $notification)
                @php
                    $data = is_object($notification->data) ? $notification->data : (is_array($notification->data) ? (object) $notification->data : json_decode($notification->data));
                    $iconMap = [
                        'shipment' => 'bi-box-seam',
                        'delivery' => 'bi-truck',
                        'payment' => 'bi-credit-card',
                        'invoice' => 'bi-file-text',
                        'user' => 'bi-person',
                        'system' => 'bi-gear',
                        'warning' => 'bi-exclamation-triangle',
                        'success' => 'bi-check-circle',
                        'info' => 'bi-info-circle',
                        'customer' => 'bi-people',
                    ];
                    $colorMap = [
                        'shipment' => 'primary',
                        'delivery' => 'success',
                        'payment' => 'warning',
                        'invoice' => 'info',
                        'user' => 'secondary',
                        'system' => 'dark',
                        'warning' => 'danger',
                        'success' => 'success',
                        'info' => 'info',
                        'customer' => 'primary',
                    ];
                    $type = $data->type ?? $data->module ?? 'info';
                    $icon = $iconMap[$type] ?? 'bi-bell';
                    $color = $colorMap[$type] ?? 'primary';
                    $title = $data->title ?? $data->subject ?? 'Notification';
                    $message = $data->message ?? $data->body ?? '';
                    $url = $data->url ?? $data->action_url ?? null;
                @endphp
                <div class="list-group-item list-group-item-action notification-item {{ is_null($notification->read_at) ? 'border-start border-start-4 border-primary' : '' }}"
                     data-id="{{ $notification->id }}"
                     data-url="{{ $url ? route('notifications.read', $notification->id) : '#' }}"
                     role="button"
                     tabindex="0">
                    <div class="d-flex align-items-start gap-3">
                        <div class="rounded-3 bg-{{ $color }} bg-opacity-10 p-2 flex-shrink-0 mt-1">
                            <i class="bi {{ $icon }} fs-5 text-{{ $color }}"></i>
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <h6 class="mb-0 fw-bold small {{ is_null($notification->read_at) ? '' : 'text-muted' }}">
                                        {{ $title }}
                                        @if(is_null($notification->read_at))
                                            <span class="badge bg-primary rounded-pill ms-1" style="width: 8px; height: 8px; padding: 0;">&nbsp;</span>
                                        @endif
                                    </h6>
                                </div>
                                <small class="text-muted text-nowrap flex-shrink-0">
                                    {{ $notification->created_at ? $notification->created_at->diffForHumans() : '' }}
                                </small>
                            </div>
                            @if($message)
                                <p class="mb-0 text-muted small mt-1">{{ $message }}</p>
                            @endif
                            @if($url)
                                <small class="text-primary mt-1 d-inline-block">
                                    <i class="bi bi-arrow-right-circle"></i> View details
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @empty
            <div class="text-center py-5 text-muted">
                <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
                <h6 class="fw-bold">No Notifications</h6>
                <p class="small mb-0">You're all caught up! No notifications to display.</p>
                @if(request('filter') === 'unread')
                    <p class="small mt-2">
                        <a href="{{ route('notifications.index') }}" class="text-decoration-none">View all notifications</a>
                    </p>
                @endif
            </div>
        @endforelse
    </div>

    @isset($notifications)
        @if(method_exists($notifications, 'links'))
            <div class="card-footer bg-white">
                {{ $notifications->links() }}
            </div>
        @endif
    @endisset
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    document.querySelectorAll('.notification-item').forEach(function (item) {
        item.addEventListener('click', function (e) {
            const id = this.dataset.id;
            const url = this.dataset.url;
            const isUnread = this.classList.contains('border-start');

            if (!url || url === '#') return;

            if (isUnread) {
                fetch('{{ route("notifications.mark-as-read", "") }}/' + id, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                }).then(function (response) {
                    if (response.ok) {
                        item.classList.remove('border-start', 'border-start-4', 'border-primary');
                        item.querySelector('h6')?.classList.add('text-muted');
                        const badge = item.querySelector('.badge.rounded-pill');
                        if (badge) badge.remove();
                        updateUnreadCount(-1);
                    }
                }).catch(function () {});
            }

            window.location.href = url;
        });
    });

    function updateUnreadCount(change) {
        const badge = document.querySelector('.card-header .badge.bg-danger');
        const markAllBtn = document.getElementById('markAllReadForm');
        if (badge) {
            let count = parseInt(badge.textContent) + change;
            if (count <= 0) {
                badge.remove();
                if (markAllBtn) markAllBtn.remove();
            } else {
                badge.textContent = count + ' unread';
            }
        }
    }

    const markAllForm = document.getElementById('markAllReadForm');
    if (markAllForm) {
        markAllForm.addEventListener('submit', function (e) {
            e.preventDefault();
            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            }).then(function (response) {
                if (response.ok) {
                    document.querySelectorAll('.notification-item.border-start').forEach(function (item) {
                        item.classList.remove('border-start', 'border-start-4', 'border-primary');
                        item.querySelector('h6')?.classList.add('text-muted');
                        const badge = item.querySelector('.badge.rounded-pill');
                        if (badge) badge.remove();
                    });
                    const headerBadge = document.querySelector('.card-header .badge.bg-danger');
                    if (headerBadge) headerBadge.remove();
                    markAllForm.remove();
                }
            }).catch(function () {});
        });
    }
});
</script>
@endpush
