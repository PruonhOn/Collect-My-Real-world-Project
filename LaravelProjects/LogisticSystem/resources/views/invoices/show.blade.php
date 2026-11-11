@extends('layouts.admin')

@section('title', 'Invoice #' . $invoice->invoice_number . ' - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item active" aria-current="page">#{{ $invoice->invoice_number }}</li>
@endsection

@section('page-title', 'Invoice #' . $invoice->invoice_number)
@section('page-subtitle', 'Date: ' . $invoice->invoice_date->format('d M Y') . ' | Due: ' . $invoice->due_date->format('d M Y'))

@section('page-actions')
    <div class="btn-group">
        @if($invoice->status != 'paid' && $invoice->status != 'cancelled')
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                <i class="bi bi-cash"></i> Record Payment
            </button>
            <form action="{{ route('invoices.mark-paid', $invoice) }}" method="POST" class="d-inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline-success" onclick="return confirm('Mark this invoice as paid?')">
                    <i class="bi bi-check-lg"></i> Mark Paid
                </button>
            </form>
        @endif
        <a href="{{ route('invoices.download-pdf', $invoice) }}" class="btn btn-outline-secondary" target="_blank">
            <i class="bi bi-file-earmark-pdf"></i> Download PDF
        </a>
        <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-outline-primary">
            <i class="bi bi-pencil"></i> Edit
        </a>
    </div>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Invoice Details</h5>
                    @php
                        $statusClass = match($invoice->status) {
                            'paid' => 'success',
                            'partial' => 'warning',
                            'overdue' => 'danger',
                            'cancelled' => 'secondary',
                            default => 'info'
                        };
                    @endphp
                    <span class="badge bg-{{ $statusClass }} fs-6">{{ ucfirst($invoice->status) }}</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Invoice Number</small>
                            <strong>#{{ $invoice->invoice_number }}</strong>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Currency</small>
                            <strong>{{ $invoice->currency }}</strong>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Invoice Date</small>
                            <strong><i class="bi bi-calendar3"></i> {{ $invoice->invoice_date->format('d M Y') }}</strong>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Due Date</small>
                            <strong><i class="bi bi-calendar3"></i> {{ $invoice->due_date->format('d M Y') }}</strong>
                        </div>
                        @if($invoice->notes)
                            <div class="col-12 mb-3">
                                <small class="text-muted d-block">Notes</small>
                                <p class="mb-0">{{ $invoice->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Invoice Items</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Description</th>
                                    <th>Type</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoice->items as $item)
                                    <tr>
                                        <td>{{ $item->description }}</td>
                                        <td><span class="badge bg-secondary">{{ ucfirst($item->type) }}</span></td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end fw-medium">${{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No items on this invoice</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Subtotal:</td>
                                    <td class="text-end fw-bold">${{ number_format($invoice->items->sum(fn($i) => $i->quantity * $i->unit_price), 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total:</td>
                                    <td class="text-end fw-bold fs-5">${{ number_format($invoice->total, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end">Paid:</td>
                                    <td class="text-end text-success fw-medium">${{ number_format($invoice->paid, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end">Balance Due:</td>
                                    <td class="text-end {{ $invoice->balance > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                        ${{ number_format($invoice->balance, 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment History</h5>
                </div>
                <div class="card-body p-0">
                    @if($invoice->payments && $invoice->payments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Payment #</th>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th class="text-end">Amount</th>
                                        <th>Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->payments as $payment)
                                        <tr>
                                            <td><span class="badge bg-secondary">#{{ $payment->payment_number }}</span></td>
                                            <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                            <td>{{ ucfirst($payment->payment_method) }}</td>
                                            <td class="text-end text-success fw-medium">${{ number_format($payment->amount, 2) }}</td>
                                            <td><small class="text-muted">{{ $payment->reference_number ?? '—' }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end">Total Paid:</th>
                                        <th class="text-end text-success">${{ number_format($invoice->payments->sum('amount'), 2) }}</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-cash-stack fs-2 d-block mb-2"></i>
                            <p class="mb-0">No payments recorded yet</p>
                            <button type="button" class="btn btn-sm btn-outline-success mt-2" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                                <i class="bi bi-plus-lg"></i> Record Payment
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Customer Information</h5>
                </div>
                <div class="card-body">
                    @if($invoice->customer)
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                                <i class="bi bi-person fs-4 text-primary"></i>
                            </div>
                            <div>
                                <strong>{{ $invoice->customer->name }}</strong>
                                <small class="d-block text-muted">{{ $invoice->customer->email }}</small>
                                <small class="d-block text-muted">{{ $invoice->customer->phone }}</small>
                            </div>
                        </div>
                        @if($invoice->customer->address)
                            <hr>
                            <small class="text-muted d-block">Address</small>
                            <p class="mb-0">{{ $invoice->customer->address }}</p>
                        @endif
                    @else
                        <p class="text-muted mb-0">No customer linked</p>
                    @endif
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total:</span>
                        <strong>${{ number_format($invoice->total, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Paid:</span>
                        <strong class="text-success">${{ number_format($invoice->paid, 2) }}</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Balance:</span>
                        <span class="fw-bold {{ $invoice->balance > 0 ? 'text-danger' : 'text-success' }}">
                            ${{ number_format($invoice->balance, 2) }}
                        </span>
                    </div>
                    @if($invoice->shipment)
                        <hr>
                        <small class="text-muted d-block">Linked Shipment</small>
                        <a href="{{ route('shipments.show', $invoice->shipment) }}" class="text-decoration-none">
                            #{{ $invoice->shipment->shipment_number }}
                        </a>
                    @endif
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Timeline</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item">
                            <small class="text-muted">Created</small>
                            <strong class="d-block">{{ $invoice->created_at->format('d M Y H:i') }}</strong>
                        </div>
                        <div class="list-group-item">
                            <small class="text-muted">Last Updated</small>
                            <strong class="d-block">{{ $invoice->updated_at->format('d M Y H:i') }}</strong>
                        </div>
                        <div class="list-group-item">
                            <small class="text-muted">Status</small>
                            <span class="badge bg-{{ $statusClass }} d-block mt-1">{{ ucfirst($invoice->status) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="recordPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('invoices.payments.store', $invoice) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Record Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Invoice Total</label>
                            <input type="text" class="form-control" value="${{ number_format($invoice->total, 2) }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Balance Due</label>
                            <input type="text" class="form-control" value="${{ number_format($invoice->balance, 2) }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Payment Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" id="amount" name="amount" class="form-control @error('amount') is-invalid @enderror"
                                value="{{ old('amount', $invoice->balance) }}" max="{{ $invoice->balance }}" required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select id="payment_method" name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                                <option value="">Select Method...</option>
                                <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="credit_card" {{ old('payment_method') == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                                <option value="check" {{ old('payment_method') == 'check' ? 'selected' : '' }}>Check</option>
                                <option value="mobile_payment" {{ old('payment_method') == 'mobile_payment' ? 'selected' : '' }}>Mobile Payment</option>
                            </select>
                            @error('payment_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="reference_number" class="form-label">Reference Number</label>
                            <input type="text" id="reference_number" name="reference_number" class="form-control @error('reference_number') is-invalid @enderror"
                                value="{{ old('reference_number') }}" placeholder="Check #, transaction ID, etc.">
                            @error('reference_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="payment_date" class="form-label">Payment Date</label>
                            <input type="date" id="payment_date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror"
                                value="{{ old('payment_date', date('Y-m-d')) }}">
                            @error('payment_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="payment_notes" class="form-label">Notes</label>
                            <textarea id="payment_notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg"></i> Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
