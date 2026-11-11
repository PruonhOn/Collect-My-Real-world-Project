@extends('layouts.app')

@section('title', 'Add Category')
@section('heading', 'Add Category')
@section('subheading', 'Create a new category or subcategory')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('categories.store') }}">
                @csrf
                @include('categories.partials.form-fields')
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-check-lg me-1"></i>Create Category</button>
                    <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
