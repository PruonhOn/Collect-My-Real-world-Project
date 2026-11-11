<div class="status-timeline">
    @php
        $statusLabels = [
            'pending' => 'Pending',
            'booked' => 'Booked',
            'in_transit' => 'In Transit',
            'arrived' => 'Arrived',
            'out_for_delivery' => 'Out for Delivery',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
        ];

        $statusIcons = [
            'pending' => 'bi-clock',
            'booked' => 'bi-journal-check',
            'in_transit' => 'bi-truck',
            'arrived' => 'bi-geo-alt',
            'out_for_delivery' => 'bi-box-seam',
            'delivered' => 'bi-check2-all',
            'cancelled' => 'bi-x-circle',
        ];

        $statusColors = [
            'pending' => 'warning',
            'booked' => 'info',
            'in_transit' => 'primary',
            'arrived' => 'secondary',
            'out_for_delivery' => 'dark',
            'delivered' => 'success',
            'cancelled' => 'danger',
        ];
    @endphp

    @forelse($statuses as $status)
        @php
            $color = $statusColors[$status->status] ?? 'secondary';
        @endphp
        <div class="timeline-item">
            <div class="timeline-marker bg-{{ $color }}">
                <i class="bi {{ $statusIcons[$status->status] ?? 'bi-circle' }} text-white"></i>
            </div>
            <div class="timeline-content">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1 fw-medium">
                            {{ $statusLabels[$status->status] ?? ucfirst($status->status) }}
                        </h6>
                        @if($status->note)
                            <p class="mb-1 text-muted small">{{ $status->note }}</p>
                        @endif
                        @if($status->user)
                            <small class="text-muted">by {{ $status->user->name }}</small>
                        @endif
                    </div>
                    <small class="text-muted text-nowrap ms-2">
                        {{ $status->created_at->format('d M Y H:i') }}
                    </small>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center text-muted py-4">
            <i class="bi bi-activity fs-2 d-block mb-2"></i>
            <p class="mb-0 small">No status updates recorded yet</p>
        </div>
    @endforelse
</div>

<style>
    .status-timeline {
        position: relative;
        padding-left: 20px;
    }

    .status-timeline::before {
        content: '';
        position: absolute;
        left: 18px;
        top: 8px;
        bottom: 8px;
        width: 2px;
        background-color: #e9ecef;
    }

    .timeline-item {
        position: relative;
        padding-bottom: 1.5rem;
    }

    .timeline-item:last-child {
        padding-bottom: 0;
    }

    .timeline-marker {
        position: absolute;
        left: -20px;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1;
        font-size: 14px;
    }

    .timeline-content {
        padding-left: 2rem;
    }
</style>
