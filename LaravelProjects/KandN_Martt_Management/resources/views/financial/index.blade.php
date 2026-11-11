@extends('layouts.app')
@section('title', 'Financial Management')
@section('page-title', 'Financial Management')

@push('styles')
<style>
    .summary-card .card-body { padding: 1.25rem; }
    .summary-card .stat-value { font-size: 1.5rem; font-weight: 700; }
    .summary-card .stat-label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; }
</style>
@endpush

@section('content')
<ul class="nav nav-tabs mb-4" id="financialTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="expenses-tab" data-bs-toggle="tab" data-bs-target="#expenses" type="button" role="tab">
            <i class="bi bi-arrow-up-circle me-1 text-danger"></i>Expenses
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="incomes-tab" data-bs-toggle="tab" data-bs-target="#incomes" type="button" role="tab">
            <i class="bi bi-arrow-down-circle me-1 text-success"></i>Incomes
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="pnl-tab" data-bs-toggle="tab" data-bs-target="#pnl" type="button" role="tab">
            <i class="bi bi-bar-chart me-1 text-primary"></i>Profit & Loss
        </button>
    </li>
</ul>

<div class="tab-content" id="financialTabContent">
    <div class="tab-pane fade show active" id="expenses" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                <h5 class="mb-0"><i class="bi bi-arrow-up-circle me-2 text-danger"></i>Expenses</h5>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#transactionModal" onclick="resetTransactionForm('expense')">
                    <i class="bi bi-plus-lg me-1"></i>Add Expense
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="expensesTable">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Category</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenses ?? [] as $expense)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $expense->category }}</td>
                                <td>{{ $expense->date ? $expense->date->format('Y-m-d') : '—' }}</td>
                                <td class="text-danger fw-semibold">{{ number_format($expense->amount, 2) }}</td>
                                <td>{{ $expense->payment_method ?? '—' }}</td>
                                <td>{{ Str::limit($expense->description, 40) }}</td>
                                <td>{!! $expense->status ? '<span class="badge bg-success">Approved</span>' : '<span class="badge bg-warning text-dark">Pending</span>' !!}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" title="Edit" onclick="editTransaction('expense', {{ $expense->id }})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete({{ $expense->id }}, 'expense of {{ number_format($expense->amount, 2) }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No expenses found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="incomes" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                <h5 class="mb-0"><i class="bi bi-arrow-down-circle me-2 text-success"></i>Incomes</h5>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#transactionModal" onclick="resetTransactionForm('income')">
                    <i class="bi bi-plus-lg me-1"></i>Add Income
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="incomesTable">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Category</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($incomes ?? [] as $income)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $income->category }}</td>
                                <td>{{ $income->date ? $income->date->format('Y-m-d') : '—' }}</td>
                                <td class="text-success fw-semibold">{{ number_format($income->amount, 2) }}</td>
                                <td>{{ $income->payment_method ?? '—' }}</td>
                                <td>{{ Str::limit($income->description, 40) }}</td>
                                <td>{!! $income->status ? '<span class="badge bg-success">Approved</span>' : '<span class="badge bg-warning text-dark">Pending</span>' !!}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" title="Edit" onclick="editTransaction('income', {{ $income->id }})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete({{ $income->id }}, 'income of {{ number_format($income->amount, 2) }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No incomes found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="pnl" role="tabpanel">
        <div class="row g-3 mb-3">
            <div class="col-auto">
                <label class="form-label small">From</label>
                <input type="date" id="pnlFrom" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <label class="form-label small">To</label>
                <input type="date" id="pnlTo" class="form-control form-control-sm">
            </div>
            <div class="col-auto d-flex align-items-end">
                <button class="btn btn-sm btn-outline-primary" onclick="loadPnL()"><i class="bi bi-search me-1"></i>Generate</button>
            </div>
        </div>

        <div class="row g-3 mb-4" id="pnlSummary">
            <div class="col-md-3">
                <div class="card shadow-sm summary-card border-success">
                    <div class="card-body">
                        <div class="stat-label text-success"><i class="bi bi-arrow-down-circle me-1"></i>Total Revenue</div>
                        <div class="stat-value text-success" id="totalRevenue">0.00</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm summary-card border-danger">
                    <div class="card-body">
                        <div class="stat-label text-danger"><i class="bi bi-arrow-up-circle me-1"></i>Total Expenses</div>
                        <div class="stat-value text-danger" id="totalExpenses">0.00</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm summary-card border-primary">
                    <div class="card-body">
                        <div class="stat-label text-primary"><i class="bi bi-cash me-1"></i>Net Profit</div>
                        <div class="stat-value text-primary" id="netProfit">0.00</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm summary-card border-info">
                    <div class="card-body">
                        <div class="stat-label text-info"><i class="bi bi-percent me-1"></i>Profit Margin</div>
                        <div class="stat-value text-info" id="profitMargin">0%</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-table me-1"></i>Transaction Details</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="pnlTable">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="pnlTableBody">
                            <tr><td colspan="5" class="text-center text-muted py-4">Select date range and click Generate.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="transactionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <form id="transactionForm" method="POST" action="" class="modal-content">
            @csrf
            <input type="hidden" name="_method" id="methodField" value="POST">
            <input type="hidden" name="id" id="transactionId">
            <input type="hidden" name="type" id="transactionType">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="bi bi-plus-circle me-2"></i>Add Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category" id="category" class="form-select" required>
                            <option value="">Select Category</option>
                            @foreach($transactionCategories ?? [] as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" id="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" id="amount" class="form-control" min="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" id="paymentMethod" class="form-select">
                            <option value="">Select Method</option>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Check">Check</option>
                            <option value="Online Payment">Online Payment</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="1">Approved</option>
                            <option value="0">Pending</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
            </div>
        </form>
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
                <p class="mb-0">Delete <strong id="deleteItemName"></strong>?</p>
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
    let expensesTable, incomesTable;

    $(document).ready(function () {
        expensesTable = $('#expensesTable').DataTable({ pageLength: 25, order: [[2, 'desc']] });
        incomesTable = $('#incomesTable').DataTable({ pageLength: 25, order: [[2, 'desc']] });
    });

    function resetTransactionForm(type) {
        $('#transactionForm')[0].reset();
        $('#methodField').val('POST');
        $('#transactionId').val('');
        $('#transactionType').val(type);
        $('#date').val('{{ date("Y-m-d") }}');
        const isExpense = type === 'expense';
        const route = isExpense ? '{{ route("financial.expenses.store") }}' : '{{ route("financial.incomes.store") }}';
        $('#transactionForm').attr('action', route);
        $('#modalTitle').html(`<i class="bi bi-plus-circle me-2 text-${isExpense ? 'danger' : 'success'}"></i>${isExpense ? 'Add Expense' : 'Add Income'}`);
    }

    function editTransaction(type, id) {
        const endpoint = type === 'expense' ? '{{ url("financial/expenses") }}' : '{{ url("financial/incomes") }}';
        const storeRoute = type === 'expense' ? '{{ route("financial.expenses.store") }}' : '{{ route("financial.incomes.store") }}';
        $.get(endpoint + '/' + id, function (data) {
            $('#modalTitle').html(`<i class="bi bi-pencil me-2"></i>Edit ${type === 'expense' ? 'Expense' : 'Income'}`);
            $('#methodField').val('PUT');
            $('#transactionForm').attr('action', storeRoute + '/' + id);
            $('#transactionId').val(data.id);
            $('#transactionType').val(type);
            $('#category').val(data.category);
            $('#date').val(data.date ? data.date.substring(0, 10) : '');
            $('#amount').val(data.amount);
            $('#paymentMethod').val(data.payment_method);
            $('#description').val(data.description);
            $('#status').val(data.status);
            $('#transactionModal').modal('show');
        });
    }

    function loadPnL() {
        const from = $('#pnlFrom').val();
        const to = $('#pnlTo').val();
        $.get('{{ route("financial.pnl") }}', { from: from, to: to }, function (data) {
            $('#totalRevenue').text(parseFloat(data.total_revenue || 0).toFixed(2));
            $('#totalExpenses').text(parseFloat(data.total_expenses || 0).toFixed(2));
            const profit = (data.total_revenue || 0) - (data.total_expenses || 0);
            $('#netProfit').text(profit.toFixed(2));
            const margin = data.total_revenue > 0 ? ((profit / data.total_revenue) * 100).toFixed(1) : 0;
            $('#profitMargin').text(margin + '%');

            let rows = '';
            if (data.transactions && data.transactions.length) {
                data.transactions.forEach(function (t) {
                    const cls = t.type === 'income' ? 'text-success' : 'text-danger';
                    rows += `<tr>
                        <td>${t.date ? t.date.substring(0,10) : '—'}</td>
                        <td><span class="badge bg-${t.type === 'income' ? 'success' : 'danger'}">${t.type}</span></td>
                        <td>${t.category}</td>
                        <td>${t.description || '—'}</td>
                        <td class="text-end fw-semibold ${cls}">${parseFloat(t.amount).toFixed(2)}</td>
                    </tr>`;
                });
            } else {
                rows = '<tr><td colspan="5" class="text-center text-muted py-4">No transactions found for selected period.</td></tr>';
            }
            $('#pnlTableBody').html(rows);
        });
    }

    function confirmDelete(id, name) {
        const type = $('#transactionType').val();
        const endpoint = type === 'expense' ? '{{ url("financial/expenses") }}' : '{{ url("financial/incomes") }}';
        $('#deleteItemName').text(name);
        $('#deleteForm').attr('action', endpoint + '/' + id);
        $('#deleteModal').modal('show');
    }
</script>
@endpush
