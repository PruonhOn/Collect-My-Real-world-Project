@extends('layouts.app')

@section('title', 'Add Customer')
@section('heading', 'Add Customer')
@section('subheading', 'Add a new customer to your database')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('customers.store') }}">
                @csrf
                @include('customers.partials.form-fields')
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-check-lg me-1"></i>Create Customer</button>
                    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
