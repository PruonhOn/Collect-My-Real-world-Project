@extends('layouts.app')

@section('title', 'Edit '.$customer->name)
@section('heading', 'Edit Customer')
@section('subheading', $customer->email ?? $customer->phone ?? $customer->name)

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('customers.update', $customer) }}">
                @csrf
                @method('PUT')
                @include('customers.partials.form-fields')
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-check-lg me-1"></i>Update Customer</button>
                    <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
