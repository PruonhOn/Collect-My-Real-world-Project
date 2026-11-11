@extends('layouts.admin')

@section('title', 'Customers Report PDF')

@section('content')
    <div class="text-center">
        <h3>Customers Report</h3>
        <p class="text-muted">{{ date('d M Y') }}</p>
        <hr>
        <p class="text-muted">PDF export functionality - customers data will be rendered here.</p>
    </div>
@endsection
