<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Reports</h2>
    </x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <a href="{{ route('admin.reports.student') }}" class="bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition group">
            <div class="flex items-center mb-4">
                <div class="p-3 bg-indigo-100 rounded-lg group-hover:bg-indigo-200 transition">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
            </div>
            <h3 class="text-lg font-medium text-gray-900">Student Report</h3>
            <p class="mt-1 text-sm text-gray-500">View individual student attendance records and summaries.</p>
        </a>

        <a href="{{ route('admin.reports.class') }}" class="bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition group">
            <div class="flex items-center mb-4">
                <div class="p-3 bg-green-100 rounded-lg group-hover:bg-green-200 transition">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
            <h3 class="text-lg font-medium text-gray-900">Class Report</h3>
            <p class="mt-1 text-sm text-gray-500">View attendance by class section with export options.</p>
        </a>

        <a href="{{ route('admin.reports.department') }}" class="bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition group">
            <div class="flex items-center mb-4">
                <div class="p-3 bg-yellow-100 rounded-lg group-hover:bg-yellow-200 transition">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <h3 class="text-lg font-medium text-gray-900">Department Report</h3>
            <p class="mt-1 text-sm text-gray-500">View attendance statistics by department.</p>
        </a>

        <a href="{{ route('admin.reports.term') }}" class="bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition group">
            <div class="flex items-center mb-4">
                <div class="p-3 bg-purple-100 rounded-lg group-hover:bg-purple-200 transition">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <h3 class="text-lg font-medium text-gray-900">Term Report</h3>
            <p class="mt-1 text-sm text-gray-500">View attendance summaries for an academic term.</p>
        </a>
    </div>
</x-app-layout>
