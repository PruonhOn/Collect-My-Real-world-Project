@extends('layouts.admin')
@section('title', 'Settings - K & N AutoCar')
@section('header', 'Website Settings')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-6 md:p-8">
        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf

            {{-- General Settings --}}
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-lg">General Settings</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Site Name</label>
                        <input type="text" name="site_name" value="{{ $settings['site_name'] ?? 'K & N AutoCar' }}" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Footer Text</label>
                        <input type="text" name="footer_text" value="{{ $settings['footer_text'] ?? '' }}" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Site Description</label>
                        <textarea name="site_description" rows="2" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">{{ $settings['site_description'] ?? '' }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">About Short</label>
                        <textarea name="about_short" rows="3" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">{{ $settings['about_short'] ?? '' }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Contact Information --}}
            <div class="mb-8 pt-6 border-t border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-8 h-8 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-lg">Contact Information</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Phone</label>
                        <input type="text" name="phone" value="{{ $settings['phone'] ?? '' }}" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                        <input type="email" name="email" value="{{ $settings['email'] ?? '' }}" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Address</label>
                        <input type="text" name="address" value="{{ $settings['address'] ?? '' }}" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Google Maps Embed URL</label>
                        <input type="url" name="map_embed_url" value="{{ $settings['map_embed_url'] ?? '' }}" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                    </div>
                </div>
            </div>

            {{-- Social Media --}}
            <div class="mb-8 pt-6 border-t border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-lg">Social Media</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Facebook URL</label>
                        <input type="url" name="facebook_url" value="{{ $settings['facebook_url'] ?? '' }}" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">YouTube URL</label>
                        <input type="url" name="youtube_url" value="{{ $settings['youtube_url'] ?? '' }}" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Instagram URL</label>
                        <input type="url" name="instagram_url" value="{{ $settings['instagram_url'] ?? '' }}" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                    </div>
                </div>
            </div>

            {{-- Payment Settings --}}
            <div class="pt-6 border-t border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-lg">Payment Settings</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Currency</label>
                        <select name="currency" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                            <option value="USD" {{ ($settings['currency'] ?? 'USD') == 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="KHR" {{ ($settings['currency'] ?? '') == 'KHR' ? 'selected' : '' }}>KHR (Riel)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">PayWay API URL</label>
                        <input type="url" name="payway_api_url" value="{{ $settings['payway_api_url'] ?? '' }}" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">PayWay API Key</label>
                        <input type="text" name="payway_api_key" value="{{ $settings['payway_api_key'] ?? '' }}" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">PayWay Merchant ID</label>
                        <input type="text" name="payway_merchant_id" value="{{ $settings['payway_merchant_id'] ?? '' }}" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:border-red-500 dark:text-gray-200 transition-all">
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end pt-6 border-t border-gray-100 dark:border-gray-800">
                <button type="submit" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-8 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm hover:shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
