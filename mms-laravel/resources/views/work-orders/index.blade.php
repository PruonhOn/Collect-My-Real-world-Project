{{-- resources/views/work-orders/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Work Orders')
@section('page-title', 'Work Orders')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Work Orders</h1>
        <p class="text-sm text-gray-500 mt-1">Manage and track all maintenance tasks</p>
    </div>
    @can('create work-orders')
    <div class="flex gap-2">
        <a href="{{ route('work-orders.create') }}" class="btn-primary">+ New Work Order</a>
    </div>
    @endcan
</div>

@livewire('work-orders.work-order-table')
@endsection
