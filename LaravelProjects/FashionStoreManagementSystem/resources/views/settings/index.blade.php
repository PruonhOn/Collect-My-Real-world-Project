@extends('layouts.app')

@section('title', 'Settings')
@section('heading', 'Store Settings')
@section('subheading', 'Store profile, currency and tax configuration')

@section('content')
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="settings-logo mb-3">
                        @if (store_logo_url())
                            <img src="{{ store_logo_url() }}" alt="Store logo">
                        @else
                            <i class="bi bi-bag-heart-fill"></i>
                        @endif
                    </div>
                    <h6>{{ $appSettings['store_name'] ?? 'Fashion Store' }}</h6>
                    <p class="text-muted small mb-0">Upload a new logo using the form. Recommended size 200×200 px (PNG, JPG or SVG).</p>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-transparent"><h6 class="mb-0 fw-semibold"><i class="bi bi-sliders me-2"></i>Store Configuration</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Store name <span class="text-danger">*</span></label>
                                <input type="text" name="store_name" value="{{ old('store_name', $appSettings['store_name'] ?? '') }}" class="form-control @error('store_name') is-invalid @enderror" required>
                                @error('store_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Currency symbol</label>
                                <input type="text" name="currency" value="{{ old('currency', $appSettings['currency'] ?? '$') }}" class="form-control" maxlength="10">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Tagline</label>
                                <input type="text" name="store_tagline" value="{{ old('store_tagline', $appSettings['store_tagline'] ?? '') }}" class="form-control" placeholder="e.g. Elegant fashion for every occasion">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="store_phone" value="{{ old('store_phone', $appSettings['store_phone'] ?? '') }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="store_email" value="{{ old('store_email', $appSettings['store_email'] ?? '') }}" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="store_address" rows="2" class="form-control">{{ old('store_address', $appSettings['store_address'] ?? '') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tax rate (%)</label>
                                <div class="input-group">
                                    <input type="number" name="tax_rate" step="0.01" min="0" max="99.99" value="{{ old('tax_rate', $appSettings['tax_rate'] ?? '0') }}" class="form-control" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                @error('tax_rate')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Store logo</label>
                                <input type="file" name="logo" class="form-control" accept="image/*">
                                @error('logo')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Receipt footer</label>
                                <textarea name="receipt_footer" rows="2" class="form-control">{{ old('receipt_footer', $appSettings['receipt_footer'] ?? '') }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-check-lg me-1"></i>Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
