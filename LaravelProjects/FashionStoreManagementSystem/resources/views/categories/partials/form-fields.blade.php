<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Category name <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name', $category->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Slug</label>
        <input type="text" name="slug" value="{{ old('slug', $category->slug ?? '') }}" class="form-control @error('slug') is-invalid @enderror" placeholder="auto-generated from name">
        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Parent category</label>
        <select name="parent_id" class="form-select">
            <option value="">— None (main category) —</option>
            @foreach ($parents as $parent)
                <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id ?? '') == $parent->id)>{{ $parent->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Status</label>
        <select name="is_active" class="form-select">
            <option value="1" @selected((int) old('is_active', $category->is_active ?? 1) === 1)>Active</option>
            <option value="0" @selected((int) old('is_active', $category->is_active ?? 1) === 0)>Inactive</option>
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" rows="3" class="form-control">{{ old('description', $category->description ?? '') }}</textarea>
    </div>
</div>
