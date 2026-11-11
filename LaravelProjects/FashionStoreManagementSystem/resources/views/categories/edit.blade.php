@extends('layouts.app')

@section('title', 'Edit '.$category->name)
@section('heading', 'Edit Category')
@section('subheading', $category->full_name)

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('categories.update', $category) }}">
                @csrf
                @method('PUT')
                @include('categories.partials.form-fields')
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-check-lg me-1"></i>Update Category</button>
                    <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
