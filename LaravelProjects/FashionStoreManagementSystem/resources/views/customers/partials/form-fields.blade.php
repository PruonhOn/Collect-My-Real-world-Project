<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Full name <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name', $customer->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $customer->phone ?? '') }}" class="form-control @error('phone') is-invalid @enderror" placeholder="e.g. +1 555 000 1234">
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" value="{{ old('email', $customer->email ?? '') }}" class="form-control @error('email') is-invalid @enderror">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Loyalty points</label>
        <input type="number" name="loyalty_points" min="0" value="{{ old('loyalty_points', $customer->loyalty_points ?? 0) }}" class="form-control">
    </div>
    <div class="col-12">
        <label class="form-label">Address</label>
        <textarea name="address" rows="3" class="form-control">{{ old('address', $customer->address ?? '') }}</textarea>
    </div>
</div>
