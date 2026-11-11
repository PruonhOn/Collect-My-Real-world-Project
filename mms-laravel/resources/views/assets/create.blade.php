{{-- resources/views/assets/create.blade.php --}}
@extends('layouts.app')
@section('title','Add Asset')
@section('page-title','Add Asset')

@section('content')
<div class="max-w-xl">
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-5">
        <a href="{{ route('assets.index') }}" class="hover:text-gray-300">Assets</a>
        <span>/</span><span class="text-gray-300">New</span>
    </div>

    <div class="card p-6">
        <h2 class="text-base font-semibold mb-5">Add New Asset</h2>
        <form action="{{ route('assets.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Asset Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" class="input w-full" required>
                @error('name')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Asset Code *</label>
                    <input type="text" name="code" value="{{ old('code') }}" class="input w-full" placeholder="AST-001" required>
                    @error('code')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Category *</label>
                    <select name="category" class="input w-full" required>
                        <option value="">Select…</option>
                        @foreach (['HVAC','Electrical','Plumbing','Mechanical','Safety','Security','IT','Other'] as $cat)
                        <option value="{{ $cat }}" @selected(old('category')===$cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Location *</label>
                <input type="text" name="location" value="{{ old('location') }}" class="input w-full" placeholder="Building A, Floor 2" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Status</label>
                    <select name="status" class="input w-full">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="retired">Retired</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Installed Date</label>
                    <input type="date" name="installed_at" value="{{ old('installed_at') }}" class="input w-full">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Warranty Expires</label>
                <input type="date" name="warranty_expires_at" value="{{ old('warranty_expires_at') }}" class="input w-full">
            </div>
            <div class="flex justify-end gap-3 pt-2 border-t border-white/[0.06]">
                <a href="{{ route('assets.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Save Asset</button>
            </div>
        </form>
    </div>
</div>
@endsection


{{-- resources/views/assets/edit.blade.php --}}
@extends('layouts.app')
@section('title','Edit Asset')
@section('page-title','Edit Asset')

@section('content')
<div class="max-w-xl">
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-5">
        <a href="{{ route('assets.index') }}" class="hover:text-gray-300">Assets</a>
        <span>/</span>
        <a href="{{ route('assets.show',$asset) }}" class="hover:text-gray-300">{{ $asset->name }}</a>
        <span>/</span><span class="text-gray-300">Edit</span>
    </div>

    <div class="card p-6">
        <h2 class="text-base font-semibold mb-5">Edit Asset</h2>
        <form action="{{ route('assets.update',$asset) }}" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Asset Name *</label>
                <input type="text" name="name" value="{{ old('name',$asset->name) }}" class="input w-full" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Category *</label>
                    <select name="category" class="input w-full" required>
                        @foreach (['HVAC','Electrical','Plumbing','Mechanical','Safety','Security','IT','Other'] as $cat)
                        <option value="{{ $cat }}" @selected(old('category',$asset->category)===$cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Status</label>
                    <select name="status" class="input w-full">
                        @foreach (['active'=>'Active','inactive'=>'Inactive','retired'=>'Retired'] as $val=>$label)
                        <option value="{{ $val }}" @selected(old('status',$asset->status)===$val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Location *</label>
                <input type="text" name="location" value="{{ old('location',$asset->location) }}" class="input w-full" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Installed Date</label>
                    <input type="date" name="installed_at" value="{{ old('installed_at',$asset->installed_at?->toDateString()) }}" class="input w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Warranty Expires</label>
                    <input type="date" name="warranty_expires_at" value="{{ old('warranty_expires_at',$asset->warranty_expires_at?->toDateString()) }}" class="input w-full">
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-2 border-t border-white/[0.06]">
                <a href="{{ route('assets.show',$asset) }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
