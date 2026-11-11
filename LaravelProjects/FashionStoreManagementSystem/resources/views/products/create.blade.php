@extends('layouts.app')

@section('title', 'Add Product')
@section('heading', 'Add Product')
@section('subheading', 'Create a new product with its size/color variants')

@section('content')
    @include('products.partials.form')
@endsection

@push('scripts')
    @vite('resources/js/product-form.js')
@endpush
