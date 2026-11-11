@extends('layouts.app')

@section('title', 'Edit '.$product->name)
@section('heading', 'Edit Product')
@section('subheading', $product->name)

@section('content')
    @include('products.partials.form', ['product' => $product])
@endsection

@push('scripts')
    @vite('resources/js/product-form.js')
@endpush
