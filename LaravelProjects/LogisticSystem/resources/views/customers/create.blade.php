<x-admin-layout>
    @section('title', 'Create Customer - ' . config('app.name'))

    @section('breadcrumbs')
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
        <li class="breadcrumb-item active" aria-current="page">Create</li>
    @endsection

    @section('page-title', 'Create Customer')
    @section('page-subtitle', 'Add a new customer')

    <div class="row">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('customers.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" id="code" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                                <x-form-error name="code" />
                            </div>

                            <div class="col-md-4">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                <x-form-error name="name" />
                            </div>

                            <div class="col-md-4">
                                <label for="company" class="form-label">Company</label>
                                <input type="text" id="company" name="company" class="form-control @error('company') is-invalid @enderror" value="{{ old('company') }}">
                                <x-form-error name="company" />
                            </div>

                            <div class="col-md-4">
                                <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="text" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" required>
                                <x-form-error name="phone" />
                            </div>

                            <div class="col-md-4">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                                <x-form-error name="email" />
                            </div>

                            <div class="col-md-4">
                                <label for="tax_number" class="form-label">Tax Number</label>
                                <input type="text" id="tax_number" name="tax_number" class="form-control @error('tax_number') is-invalid @enderror" value="{{ old('tax_number') }}">
                                <x-form-error name="tax_number" />
                            </div>

                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea id="address" name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                                <x-form-error name="address" />
                            </div>

                            <div class="col-md-4">
                                <label for="city" class="form-label">City</label>
                                <input type="text" id="city" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city') }}">
                                <x-form-error name="city" />
                            </div>

                            <div class="col-md-4">
                                <label for="province" class="form-label">Province</label>
                                <input type="text" id="province" name="province" class="form-control @error('province') is-invalid @enderror" value="{{ old('province') }}">
                                <x-form-error name="province" />
                            </div>

                            <div class="col-md-4">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" id="country" name="country" class="form-control @error('country') is-invalid @enderror" value="{{ old('country', 'Cambodia') }}">
                                <x-form-error name="country" />
                            </div>

                            <div class="col-md-4">
                                <label for="contact_person" class="form-label">Contact Person</label>
                                <input type="text" id="contact_person" name="contact_person" class="form-control @error('contact_person') is-invalid @enderror" value="{{ old('contact_person') }}">
                                <x-form-error name="contact_person" />
                            </div>

                            <div class="col-md-4">
                                <label for="contact_phone" class="form-label">Contact Phone</label>
                                <input type="text" id="contact_phone" name="contact_phone" class="form-control @error('contact_phone') is-invalid @enderror" value="{{ old('contact_phone') }}">
                                <x-form-error name="contact_phone" />
                            </div>

                            <div class="col-md-4">
                                <label for="credit_limit" class="form-label">Credit Limit ($)</label>
                                <input type="number" step="0.01" min="0" id="credit_limit" name="credit_limit" class="form-control @error('credit_limit') is-invalid @enderror" value="{{ old('credit_limit', 0) }}">
                                <x-form-error name="credit_limit" />
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Create Customer
                            </button>
                            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
