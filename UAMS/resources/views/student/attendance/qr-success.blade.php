<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('QR Check-in Success') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                {{-- Success Animation --}}
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-green-100 rounded-full mb-4" x-data="{ show: false }" x-init="$nextTick(() => { setTimeout(() => show = true, 100) })" x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="scale-0" x-transition:enter-end="scale-100">
                        <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900">Check-in Successful!</h3>
                    <p class="mt-2 text-sm text-gray-500">Your attendance has been recorded.</p>
                </div>

                {{-- Attendance Details --}}
                <div class="max-w-md mx-auto">
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Attendance Details</h4>
                        <dl class="space-y-4">
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Course</dt>
                                <dd class="text-sm text-gray-900">{{ $courseName ?? 'N/A' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Section</dt>
                                <dd class="text-sm text-gray-900">{{ $sectionName ?? 'N/A' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Date</dt>
                                <dd class="text-sm text-gray-900">{{ now()->format('l, F j, Y') }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Time</dt>
                                <dd class="text-sm text-gray-900">{{ now()->format('g:i A') }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Present</span>
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Method</dt>
                                <dd class="text-sm text-gray-900">QR Code</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Back Button --}}
                    <div class="mt-8 text-center">
                        <a href="{{ route('student.dashboard') }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
