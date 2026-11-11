@extends('layouts.admin')
@section('title', 'Contact - K & N AutoCar')
@section('header', 'Contact Message')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
        <div class="p-6 md:p-8">
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</label>
                    <p class="font-semibold text-gray-900 dark:text-white mt-1">{{ $contact->name }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</label>
                    <a href="mailto:{{ $contact->email }}" class="font-semibold text-red-600 dark:text-red-400 hover:text-red-700 mt-1 block">{{ $contact->email }}</a>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Phone</label>
                    <p class="font-semibold text-gray-900 dark:text-white mt-1">{{ $contact->phone ?? 'N/A' }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</label>
                    <p class="font-semibold text-gray-900 dark:text-white mt-1">{{ $contact->created_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>
            <div class="mb-6">
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subject</label>
                    <p class="font-medium text-gray-900 dark:text-white mt-1">{{ $contact->subject ?? 'No subject' }}</p>
                </div>
            </div>
            <div>
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Message</label>
                    <div class="text-gray-700 dark:text-gray-300 leading-relaxed mt-2 whitespace-pre-wrap">{{ $contact->message }}</div>
                </div>
            </div>
            <div class="mt-8 flex items-center gap-3 pt-6 border-t border-gray-100 dark:border-gray-800">
                <a href="{{ route('admin.contacts.index') }}" class="inline-flex items-center gap-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to Messages
                </a>
                <form action="{{ route('admin.contacts.destroy', $contact) }}" method="POST" onsubmit="return confirm('Delete this message?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
