<x-admin-layout>
    @section('title', 'Edit Branch - ' . config('app.name'))

    @section('breadcrumbs')
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('branches.index') }}">Branches</a></li>
        <li class="breadcrumb-item"><a href="{{ route('branches.show', $branch) }}">{{ $branch->name }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit</li>
    @endsection

    @section('page-title', 'Edit Branch: ' . $branch->name)
    @section('page-subtitle', 'Update branch information')

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('branches.update', $branch) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" id="code" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $branch->code) }}" required>
                                <x-form-error name="code" />
                            </div>

                            <div class="col-md-8">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $branch->name) }}" required>
                                <x-form-error name="name" />
                            </div>

                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea id="address" name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $branch->address) }}</textarea>
                                <x-form-error name="address" />
                            </div>

                            <div class="col-md-4">
                                <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                                <input type="text" id="city" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $branch->city) }}" required>
                                <x-form-error name="city" />
                            </div>

                            <div class="col-md-4">
                                <label for="province" class="form-label">Province</label>
                                <input type="text" id="province" name="province" class="form-control @error('province') is-invalid @enderror" value="{{ old('province', $branch->province) }}">
                                <x-form-error name="province" />
                            </div>

                            <div class="col-md-4">
                                <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="text" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $branch->phone) }}" required>
                                <x-form-error name="phone" />
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $branch->email) }}">
                                <x-form-error name="email" />
                            </div>

                            <div class="col-md-6">
                                <label for="manager_name" class="form-label">Manager Name</label>
                                <input type="text" id="manager_name" name="manager_name" class="form-control @error('manager_name') is-invalid @enderror" value="{{ old('manager_name', $branch->manager_name) }}">
                                <x-form-error name="manager_name" />
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" id="is_active" name="is_active" class="form-check-input" value="1" {{ old('is_active', $branch->is_active) ? 'checked' : '' }}>
                                    <label for="is_active" class="form-check-label">Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Update Branch
                            </button>
                            <a href="{{ route('branches.show', $branch) }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
