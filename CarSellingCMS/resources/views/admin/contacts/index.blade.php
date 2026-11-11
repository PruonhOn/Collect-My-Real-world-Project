@extends('layouts.admin')
@section('title', 'Messages - K & N AutoCar')
@section('header')
<div class="flex items-center gap-3">
    <div class="w-9 h-9 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
    </div>
    <div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Contact Messages</h2>
        <p class="text-xs text-gray-500 dark:text-gray-400">Customer inquiries and feedback</p>
    </div>
</div>
@endsection

@section('content')
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50 text-left">
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Name</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Email</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Subject</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3.5 font-semibold text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contacts as $contact)
                    <tr class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors {{ !$contact->is_read ? 'bg-blue-50/30 dark:bg-blue-900/5' : '' }}">
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-600 rounded-full flex items-center justify-center text-xs font-bold text-gray-600 dark:text-gray-300 flex-shrink-0">
                                    {{ strtoupper(substr($contact->name, 0, 1)) }}
                                </div>
                                <span class="font-medium text-gray-900 dark:text-white {{ !$contact->is_read ? 'font-semibold' : '' }}">{{ $contact->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 text-gray-600 dark:text-gray-400">{{ $contact->email }}</td>
                        <td class="px-4 py-3.5 text-gray-600 dark:text-gray-400 max-w-[200px] truncate">{{ $contact->subject ?? 'No subject' }}</td>
                        <td class="px-4 py-3.5 text-xs text-gray-500 dark:text-gray-400">{{ $contact->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3.5">
                            @if($contact->is_read)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                    Read
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                    New
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.contacts.show', $contact) }}" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <form action="{{ route('admin.contacts.destroy', $contact) }}" method="POST" onsubmit="return confirm('Delete this message?')">
                                    @csrf @method('DELETE')
                                    <button class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-16 text-center">
                            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">No messages yet</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($contacts, 'links'))
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
            {{ $contacts->links() }}
        </div>
    @endif
</div>
@endsection
