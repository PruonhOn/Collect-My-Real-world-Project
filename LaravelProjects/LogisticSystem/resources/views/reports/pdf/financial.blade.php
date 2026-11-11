@extends('layouts.admin')

@section('title', 'Financial Report PDF')

@section('content')
    <div class="text-center">
        <h3>Financial Report</h3>
        <p class="text-muted">{{ date('d M Y') }}</p>
        <hr>
        <p class="text-muted">PDF export functionality - financial data will be rendered here.</p>
    </div>
@endsection
