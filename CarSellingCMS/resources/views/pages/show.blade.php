@extends('layouts.public')
@section('title', $page->title . ' - K & N AutoCar')
@section('meta_description', $page->meta_description)

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-4xl font-bold text-gray-900 mb-8">{{ $page->title }}</h1>
    <div class="prose prose-lg max-w-none text-gray-700 leading-relaxed">
        {!! $page->content !!}
    </div>
</section>
@endsection