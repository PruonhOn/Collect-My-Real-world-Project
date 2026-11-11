<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Full name <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name', $staff->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Email address <span class="text-danger">*</span></label>
        <input type="email" name="email" value="{{ old('email', $staff->email ?? '') }}" class="form-control @error('email') is-invalid @enderror" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Role <span class="text-danger">*</span></label>
        <select name="role" class="form-select @error('role') is-invalid @enderror" required>
            @foreach ($roles as $role)
                <option value="{{ $role->value }}" @selected(old('role', $staff->role ?? 'cashier') == $role->value)>{{ $role->label() }}</option>
            @endforeach
        </select>
        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">
            <strong>Admin</strong> — full access · <strong>Manager</strong> — inventory, reports, sales · <strong>Cashier</strong> — POS only
        </div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Status</label>
        <select name="is_active" class="form-select">
            <option value="1" @selected((int) old('is_active', $staff->is_active ?? 1) === 1)>Active</option>
            <option value="0" @selected((int) old('is_active', $staff->is_active ?? 1) === 0)>Deactivated</option>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Password {{ isset($staff) && $staff->exists ? '(leave blank to keep current)' : '' }} <span class="text-danger">*</span></label>
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" {{ isset($staff) && $staff->exists ? '' : 'required' }}>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Confirm password</label>
        <input type="password" name="password_confirmation" class="form-control">
    </div>
</div>
