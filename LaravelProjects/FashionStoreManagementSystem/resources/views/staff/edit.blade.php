@extends('layouts.app')

@section('title', 'Edit '.$staff->name)
@section('heading', 'Edit Staff')
@section('subheading', $staff->email)

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('staff.update', $staff) }}">
                @csrf
                @method('PUT')
                @include('staff.partials.form-fields')
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-check-lg me-1"></i>Update Account</button>
                    <a href="{{ route('staff.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
