@extends('layouts.admin')

@section('title', 'Warehouses Report PDF')

@section('content')
    <div class="text-center">
        <h3>Warehouses Report</h3>
        <p class="text-muted">{{ date('d M Y') }}</p>
        <hr>
        <p class="text-muted">PDF export functionality - warehouses data will be rendered here.</p>
    </div>
@endsection
