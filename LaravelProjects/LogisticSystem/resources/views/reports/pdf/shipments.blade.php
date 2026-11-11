@extends('layouts.admin')

@section('title', 'Shipments Report PDF')

@section('content')
    <div class="text-center">
        <h3>Shipments Report</h3>
        <p class="text-muted">{{ date('d M Y') }}</p>
        <hr>
        <p class="text-muted">PDF export functionality - shipments data will be rendered here.</p>
    </div>
@endsection
