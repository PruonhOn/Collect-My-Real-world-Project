@extends('layouts.app')
@section('title', 'Sales')
@section('page-title', 'Sales')

@section('content')
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center bg-white">
        <h5 class="mb-0"><i class="bi bi-cart-check me-2 text-primary"></i>All Sales</h5>
        <button class="btn btn-primary" onclick="location.href='{{ route('sales.create') }}'">
            <i class="bi bi-plus-lg me-1"></i>New Sale
        </button>
    </div>
    <div class="card-body">
        <div class="row g-2 mb-3 align-items-end">
            <div class="col-auto">
                <label class="form-label small">From</label>
                <input type="date" id="dateFrom" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <label class="form-label small">To</label>
                <input type="date" id="dateTo" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-outline-primary" onclick="filterDate()"><i class="bi bi-funnel me-1"></i>Filter</button>
                <button class="btn btn-sm btn-outline-secondary" onclick="clearFilter()"><i class="bi bi-x me-1"></i>Clear</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="salesTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Invoice No</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Grand Total</th>
                        <th>Paid</th>
                        <th>Due</th>
                        <th>Payment Status</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales ?? [] as $sale)
                    @php
                        $due = $sale->grand_total - $sale->paid;
                        if ($due <= 0) $ps = '<span class="badge bg-success">Paid</span>';
                        elseif ($due < $sale->grand_total) $ps = '<span class="badge bg-warning text-dark">Partial</span>';
                        else $ps = '<span class="badge bg-danger">Unpaid</span>';
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><code>{{ $sale->invoice_no }}</code></td>
                        <td>{{ $sale->customer->name ?? 'Walk-in' }}</td>
                        <td>{{ $sale->date ? $sale->date->format('Y-m-d') : '—' }}</td>
                        <td>{{ number_format($sale->grand_total, 2) }}</td>
                        <td>{{ number_format($sale->paid, 2) }}</td>
                        <td class="text-{{ $due > 0 ? 'danger' : 'success' }}">{{ number_format($due, 2) }}</td>
                        <td>{!! $ps !!}</td>
                        <td>{!! $sale->status ? '<span class="badge bg-success">Completed</span>' : '<span class="badge bg-secondary">Cancelled</span>' !!}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-info" title="View" onclick="viewSale({{ $sale->id }})">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary" title="Edit" onclick="editSale({{ $sale->id }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete({{ $sale->id }}, '{{ $sale->invoice_no }}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No sales found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i>Sale Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewContent">
                <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printInvoice()"><i class="bi bi-printer me-1"></i>Print Invoice</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h6 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Confirm Delete</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-3">
                <p class="mb-0">Delete sale <strong id="deleteItemName"></strong>?</p>
                <small class="text-muted">This action cannot be undone.</small>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let table;
    $(document).ready(function () {
        table = $('#salesTable').DataTable({
            pageLength: 25,
            order: [[3, 'desc']]
        });
    });

    function filterDate() {
        const from = $('#dateFrom').val();
        const to = $('#dateTo').val();
        $.fn.dataTable.ext.search.push(function (settings, data) {
            const date = data[3];
            if (!from && !to) return true;
            if (from && date < from) return false;
            if (to && date > to) return false;
            return true;
        });
        table.draw();
        $.fn.dataTable.ext.search.pop();
    }

    function clearFilter() {
        $('#dateFrom').val('');
        $('#dateTo').val('');
        table.draw();
    }

    function viewSale(id) {
        $('#viewContent').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
        $('#viewModal').modal('show');
        $.get('{{ url("sales") }}/' + id, function (data) {
            const due = data.grand_total - data.paid;
            let ps = 'Unpaid', cls = 'danger';
            if (due <= 0) { ps = 'Paid'; cls = 'success'; }
            else if (due < data.grand_total) { ps = 'Partial'; cls = 'warning text-dark'; }

            let itemsHtml = '';
            if (data.items && data.items.length) {
                data.items.forEach(function (item) {
                    itemsHtml += `<tr><td>${item.product ? item.product.name : '—'}</td><td>${item.quantity}</td><td class="text-end">${parseFloat(item.price).toFixed(2)}</td><td class="text-end">${(item.quantity * item.price).toFixed(2)}</td></tr>`;
                });
            }

            const html = `
                <div class="row g-3 mb-3">
                    <div class="col-md-4"><strong>Invoice:</strong><br><code>${data.invoice_no}</code></div>
                    <div class="col-md-4"><strong>Customer:</strong><br>${data.customer ? data.customer.name : 'Walk-in Customer'}</div>
                    <div class="col-md-4"><strong>Date:</strong><br>${data.date ? data.date.substring(0,10) : '—'}</div>
                </div>
                <h6 class="fw-semibold mb-2">Items</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr><th>Product</th><th>Qty</th><th class="text-end">Price</th><th class="text-end">Total</th></tr>
                        </thead>
                        <tbody>${itemsHtml}</tbody>
                        <tfoot>
                            <tr class="fw-semibold"><td colspan="3" class="text-end">Grand Total</td><td class="text-end">${parseFloat(data.grand_total).toFixed(2)}</td></tr>
                            <tr><td colspan="3" class="text-end">Paid</td><td class="text-end">${parseFloat(data.paid).toFixed(2)}</td></tr>
                            <tr class="text-${cls}"><td colspan="3" class="text-end">Due</td><td class="text-end">${due.toFixed(2)}</td></tr>
                            <tr><td colspan="3" class="text-end">Payment Status</td><td class="text-end"><span class="badge bg-${cls}">${ps}</span></td></tr>
                        </tfoot>
                    </table>
                </div>`;
            $('#viewContent').html(html);
        }).fail(function () {
            $('#viewContent').html('<div class="alert alert-danger mb-0">Failed to load sale details.</div>');
        });
    }

    function printInvoice() {
        const content = $('#viewContent').html();
        const win = window.open('', '_blank');
        win.document.write('<html><head><title>Invoice</title>');
        win.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">');
        win.document.write('</head><body><div class="container mt-4">' + content + '</div></body></html>');
        win.document.close();
        win.print();
    }

    function editSale(id) {
        window.location.href = '{{ url("sales") }}/' + id + '/edit';
    }

    function confirmDelete(id, name) {
        $('#deleteItemName').text(name);
        $('#deleteForm').attr('action', '{{ url("sales") }}/' + id);
        $('#deleteModal').modal('show');
    }
</script>
@endpush
