@extends('layouts.app')

@section('title', 'Order '.$order->order_number)
@section('heading', $order->order_number)
@section('subheading', $order->created_at->format('d M Y, H:i'))

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-basket me-2"></i>Items</h6>
                    <span class="badge {{ $order->status->value === 'refunded' ? 'bg-danger-subtle text-danger' : ($order->status->value === 'partially_refunded' ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success') }}">{{ $order->status->label() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Variant</th>
                                <th class="text-center">Qty</th>
                                <th class="text-center">Refunded</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td><strong>{{ $item->product_name }}</strong><div class="small text-muted">{{ $item->sku }}</div></td>
                                    <td>{{ $item->variant_label ?: 'Default' }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-center">
                                        @if ($item->refunded_quantity > 0)
                                            <span class="badge bg-danger-subtle text-danger">{{ $item->refunded_quantity }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ money($item->unit_price) }}</td>
                                    <td class="text-end fw-semibold">{{ money($item->line_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($order->refunds->count())
                <div class="card">
                    <div class="card-header bg-transparent">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-arrow-counterclockwise me-2"></i>Refunds</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Refund #</th>
                                    <th>Date</th>
                                    <th>By</th>
                                    <th>Reason</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->refunds as $refund)
                                    <tr>
                                        <td><strong>{{ $refund->refund_number }}</strong></td>
                                        <td>{{ $refund->created_at->format('d M Y H:i') }}</td>
                                        <td>{{ $refund->user?->name ?? '—' }}</td>
                                        <td class="small">{{ $refund->reason }}</td>
                                        <td class="text-end fw-semibold text-danger">− {{ money($refund->amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header bg-transparent"><h6 class="mb-0 fw-semibold"><i class="bi bi-card-text me-2"></i>Summary</h6></div>
                <div class="card-body">
                    <div class="summary-row"><span>Subtotal</span><span>{{ money($order->subtotal) }}</span></div>
                    @if ((float) $order->discount_amount > 0)
                        <div class="summary-row"><span>Discount ({{ $order->discount_type }}{{ $order->discount_type === 'percent' ? ' '.$order->discount_value.'%' : '' }})</span><span class="text-success">− {{ money($order->discount_amount) }}</span></div>
                    @endif
                    <div class="summary-row"><span>Tax ({{ rtrim(rtrim((string) $order->tax_rate, '0'), '.') }}%)</span><span>{{ money($order->tax_amount) }}</span></div>
                    <hr>
                    <div class="summary-row fs-5"><strong>Total</strong><strong>{{ money($order->total) }}</strong></div>
                    @if ((float) $order->refunded_amount > 0)
                        <div class="summary-row text-danger"><span>Refunded</span><span>− {{ money($order->refunded_amount) }}</span></div>
                        <div class="summary-row"><span><strong>Net paid</strong></span><span><strong>{{ money((float) $order->total - (float) $order->refunded_amount) }}</strong></span></div>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-transparent"><h6 class="mb-0 fw-semibold"><i class="bi bi-person me-2"></i>Details</h6></div>
                <div class="card-body">
                    <div class="summary-row"><span>Customer</span><span>{{ $order->customer?->name ?? 'Walk-in' }}</span></div>
                    <div class="summary-row"><span>Cashier</span><span>{{ $order->user?->name ?? '—' }}</span></div>
                    <div class="summary-row"><span>Payment method</span><span>{{ $order->payment_method?->label() }}</span></div>
                    <div class="summary-row"><span>Tendered</span><span>{{ $order->amount_tendered !== null ? money($order->amount_tendered) : '—' }}</span></div>
                    <div class="summary-row"><span>Change</span><span>{{ $order->change_due !== null ? money($order->change_due) : '—' }}</span></div>
                    @if ($order->notes)
                        <div class="summary-row"><span>Notes</span><span class="small">{{ $order->notes }}</span></div>
                    @endif
                </div>
            </div>

            <div class="d-grid gap-2">
                <a href="{{ route('pos.receipt', $order) }}" target="_blank" class="btn btn-outline-primary rounded-pill"><i class="bi bi-printer me-1"></i>Print Receipt</a>
                @if ($order->remaining_refundable > 0 && (auth()->user()->isAdmin() || auth()->user()->isManager()))
                    <button class="btn btn-outline-danger rounded-pill" data-bs-toggle="modal" data-bs-target="#refundModal"><i class="bi bi-arrow-counterclockwise me-1"></i>Process Refund</button>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('modals')
    @if ($order->remaining_refundable > 0 && (auth()->user()->isAdmin() || auth()->user()->isManager()))
        <div class="modal fade" id="refundModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('orders.refund', $order) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-arrow-counterclockwise me-2 text-danger"></i>Refund Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info alert-soft small">
                            Remaining refundable: <strong>{{ money($order->remaining_refundable) }}</strong>. Stock will be restored automatically.
                        </div>
                        <label class="form-label">Items to refund</label>
                        <div class="refund-items mb-3">
                            @foreach ($refundableItems as $item)
                                <div class="refund-item">
                                    <div class="flex-grow-1">
                                        <strong>{{ $item->product_name }}</strong>
                                        <small class="text-muted d-block">{{ $item->variant_label ?: 'Default' }} · {{ $item->unit_price }}</small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="small text-muted">max {{ $item->remaining_quantity }}</span>
                                        <input type="number" name="items[{{ $item->id }}][order_item_id]" value="{{ $item->id }}" hidden>
                                        <input type="number" name="items[{{ $item->id }}][quantity]" class="form-control form-control-sm" style="width:80px" min="0" max="{{ $item->remaining_quantity }}" value="0">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Reason <span class="text-danger">*</span></label>
                            <textarea name="reason" rows="2" class="form-control" placeholder="e.g. Customer returned the item" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger rounded-pill" onclick="return confirm('Process this refund? Stock will be restored.')"><i class="bi bi-check-lg me-1"></i>Process Refund</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
