@extends('layouts.admin')

@section('title', 'Deliveries Report PDF')

@section('content')
    <div class="text-center">
        <h3>Deliveries Report</h3>
        <p class="text-muted">{{ date('d M Y') }}</p>
        <hr>
        <p class="text-muted">PDF export functionality - deliveries data will be rendered here.</p>
    </div>
@endsection
