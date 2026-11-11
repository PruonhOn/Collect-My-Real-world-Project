<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Attendance - {{ $classSection->course->name ?? 'N/A' }} - {{ $classSection->name }}
            </h2>
            <div class="flex items-center space-x-3">
                <a href="{{ route('teacher.sections.show', $classSection) }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                    Back to Section
                </a>
                <button
                    x-data="{ showCreateForm: false }"
                    @click="showCreateForm = true"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 px-4 rounded-lg">
                    + New Session
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Create Session Form (Alpine.js Toggle) --}}
            <div x-data="{ showForm: false }" class="bg-white shadow-sm sm:rounded-lg p-6" x-show="showForm" x-transition>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Create New Session</h3>
                    <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form action="{{ route('teacher.attendance.store-session', $classSection) }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700">Session Date</label>
                            <input type="date" name="date" id="date" value="{{ date('Y-m-d') }}" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="start_time" class="block text-sm font-medium text-gray-700">Start Time</label>
                            <input type="time" name="start_time" id="start_time" value="{{ date('H:i') }}" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="end_time" class="block text-sm font-medium text-gray-700">End Time (Optional)</label>
                            <input type="time" name="end_time" id="end_time"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="grace_period" class="block text-sm font-medium text-gray-700">Grace Period (mins)</label>
                            <input type="number" name="grace_period" id="grace_period" value="15" min="0"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 px-4 rounded-lg">
                            Create Session
                        </button>
                    </div>
                </form>
            </div>

            {{-- Sessions Table --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Attendance Sessions</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Present</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Absent</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Late</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Excused</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($sessions as $session)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ \Carbon\Carbon::parse($session->date)->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $session->start_time }}{{ $session->end_time ? ' - ' . $session->end_time : '' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($session->status === 'active')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Closed</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $session->present_count ?? 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $session->absent_count ?? 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $session->late_count ?? 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $session->excused_count ?? 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm space-x-3">
                                        <a href="{{ route('teacher.attendance.show-session', $session) }}"
                                            class="text-indigo-600 hover:text-indigo-500">
                                            View
                                        </a>
                                        @if($session->status === 'active')
                                            <form action="{{ route('teacher.attendance.close-session', $session) }}" method="POST" class="inline"
                                                x-data
                                                @submit.prevent="if(confirm('Are you sure you want to close this session?')) $el.submit()">
                                                @csrf
                                                <button type="submit" class="text-red-600 hover:text-red-500">Close</button>
                                            </form>
                                            <a href="{{ route('teacher.attendance.show-qr', $session) }}"
                                                class="text-purple-600 hover:text-purple-500" target="_blank">
                                                Show QR
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No attendance sessions yet. Create your first session above.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $sessions->links() }}
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            // The showForm toggle is handled inline with x-data
        });
    </script>
    @endpush
</x-app-layout>
