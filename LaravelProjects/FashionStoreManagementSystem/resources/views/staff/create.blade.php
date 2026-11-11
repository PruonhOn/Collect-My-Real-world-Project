@extends('layouts.app')

@section('title', 'Add Staff')
@section('heading', 'Add Staff')
@section('subheading', 'Create a new staff account')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('staff.store') }}">
                @csrf
                @include('staff.partials.form-fields')
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-check-lg me-1"></i>Create Account</button>
                    <a href="{{ route('staff.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
