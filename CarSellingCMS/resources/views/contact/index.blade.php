@extends('layouts.public')
@section('title', 'Contact Us - K & N AutoCar')

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-3">Get In Touch</h1>
        <p class="text-gray-600 text-lg">We'd love to hear from you. Get in touch with our team.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Contact Info --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="font-bold text-gray-900 mb-4">Contact Information</h3>
                <div class="space-y-4">
                    <div class="flex items-start space-x-3">
                        <svg class="w-6 h-6 text-red-600 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <div>
                            <p class="font-medium text-gray-900">Address</p>
                            <p class="text-gray-600">{{ $address }}</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <svg class="w-6 h-6 text-red-600 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <div>
                            <p class="font-medium text-gray-900">Phone</p>
                            <p class="text-gray-600">{{ $phone }}</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <svg class="w-6 h-6 text-red-600 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <div>
                            <p class="font-medium text-gray-900">Email</p>
                            <p class="text-gray-600">{{ $email }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="font-bold text-gray-900 mb-3">Business Hours</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-600">Mon - Fri</span><span class="font-medium">8:00 AM - 6:00 PM</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Saturday</span><span class="font-medium">8:00 AM - 5:00 PM</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Sunday</span><span class="font-medium">Closed</span></div>
                </div>
            </div>
        </div>

        {{-- Contact Form --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md p-8">
                <h3 class="font-bold text-xl text-gray-900 mb-6">Send Us a Message</h3>
                <form action="{{ route('contact.store') }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                            <input type="text" name="name" required class="w-full border-gray-300 rounded-lg px-4 py-2.5 focus:ring-red-500 focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" name="email" required class="w-full border-gray-300 rounded-lg px-4 py-2.5 focus:ring-red-500 focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input type="text" name="phone" class="w-full border-gray-300 rounded-lg px-4 py-2.5 focus:ring-red-500 focus:border-red-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                            <input type="text" name="subject" required class="w-full border-gray-300 rounded-lg px-4 py-2.5 focus:ring-red-500 focus:border-red-500">
                        </div>
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                        <textarea name="message" rows="5" required class="w-full border-gray-300 rounded-lg px-4 py-2.5 focus:ring-red-500 focus:border-red-500"></textarea>
                    </div>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-8 py-3 rounded-lg font-semibold transition">Send Message</button>
                </form>
            </div>

            {{-- Map --}}
            @if($mapEmbedUrl)
                <div class="mt-6 bg-white rounded-xl shadow-md overflow-hidden h-64">
                    <iframe src="{{ $mapEmbedUrl }}" width="100%" height="100%" style="border:0;" allowfullscreen loading="lazy"></iframe>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection