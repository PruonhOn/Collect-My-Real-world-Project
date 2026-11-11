@extends('layouts.admin')

@section('title', 'Create Transaction - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finance.index') }}">Finance</a></li>
    <li class="breadcrumb-item"><a href="{{ route('transactions.index') }}">Transactions</a></li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-title', 'Create Transaction')
@section('page-subtitle', 'Record a financial transaction')

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('transactions.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="transaction_number" class="form-label">Transaction Number</label>
                                <input type="text" id="transaction_number" name="transaction_number" class="form-control @error('transaction_number') is-invalid @enderror"
                                    value="{{ old('transaction_number', $transactionNumber ?? 'TXN-' . date('Ymd') . '-001') }}" readonly>
                                @error('transaction_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" id="date" name="date" class="form-control @error('date') is-invalid @enderror"
                                    value="{{ old('date', date('Y-m-d')) }}" required>
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="">Select Type...</option>
                                    <option value="income" {{ old('type') == 'income' ? 'selected' : '' }}>Income</option>
                                    <option value="expense" {{ old('type') == 'expense' ? 'selected' : '' }}>Expense</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                <select id="category" name="category" class="form-select @error('category') is-invalid @enderror" required>
                                    <option value="">Select Category...</option>
                                    <optgroup label="Income">
                                        <option value="Shipping Revenue" {{ old('category') == 'Shipping Revenue' ? 'selected' : '' }}>Shipping Revenue</option>
                                        <option value="Service Fees" {{ old('category') == 'Service Fees' ? 'selected' : '' }}>Service Fees</option>
                                        <option value="Other Income" {{ old('category') == 'Other Income' ? 'selected' : '' }}>Other Income</option>
                                    </optgroup>
                                    <optgroup label="Expense">
                                        <option value="Fuel" {{ old('category') == 'Fuel' ? 'selected' : '' }}>Fuel</option>
                                        <option value="Maintenance" {{ old('category') == 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
                                        <option value="Salaries" {{ old('category') == 'Salaries' ? 'selected' : '' }}>Salaries</option>
                                        <option value="Utilities" {{ old('category') == 'Utilities' ? 'selected' : '' }}>Utilities</option>
                                        <option value="Rent" {{ old('category') == 'Rent' ? 'selected' : '' }}>Rent</option>
                                        <option value="Insurance" {{ old('category') == 'Insurance' ? 'selected' : '' }}>Insurance</option>
                                        <option value="Office Supplies" {{ old('category') == 'Office Supplies' ? 'selected' : '' }}>Office Supplies</option>
                                        <option value="Travel" {{ old('category') == 'Travel' ? 'selected' : '' }}>Travel</option>
                                        <option value="Other Expense" {{ old('category') == 'Other Expense' ? 'selected' : '' }}>Other Expense</option>
                                    </optgroup>
                                </select>
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" id="amount" name="amount" class="form-control @error('amount') is-invalid @enderror"
                                        value="{{ old('amount') }}" min="0.01" required>
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="currency" class="form-label">Currency</label>
                                <select id="currency" name="currency" class="form-select @error('currency') is-invalid @enderror">
                                    <option value="USD" {{ old('currency', 'USD') == 'USD' ? 'selected' : '' }}>USD ($)</option>
                                    <option value="KHR" {{ old('currency') == 'KHR' ? 'selected' : '' }}>KHR (៛)</option>
                                    <option value="THB" {{ old('currency') == 'THB' ? 'selected' : '' }}>THB (฿)</option>
                                </select>
                                @error('currency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <select id="payment_method" name="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
                                    <option value="">Select Method...</option>
                                    <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                    <option value="credit_card" {{ old('payment_method') == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                                    <option value="check" {{ old('payment_method') == 'check' ? 'selected' : '' }}>Check</option>
                                    <option value="aba" {{ old('payment_method') == 'aba' ? 'selected' : '' }}>ABA Pay</option>
                                    <option value="wing" {{ old('payment_method') == 'wing' ? 'selected' : '' }}>Wing</option>
                                </select>
                                @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="branch_id" class="form-label">Branch</label>
                                <select id="branch_id" name="branch_id" class="form-select select2 @error('branch_id') is-invalid @enderror">
                                    <option value="">All Branches</option>
                                    @foreach($branches ?? [] as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                                    <option value="completed" {{ old('status', 'completed') == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3"
                                    placeholder="Describe the transaction..." required>{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Create Transaction
                            </button>
                            <a href="{{ route('transactions.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        });
    </script>
@endsection
