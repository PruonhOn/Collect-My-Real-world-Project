<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $classSection->course->name ?? 'N/A' }} - {{ $classSection->name }}
            </h2>
            <a href="{{ route('teacher.attendance.index', $classSection) }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                Back to Sessions
            </a>
        </div>
    </x-slot>

    <div class="py-12" x-data="attendanceSession()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Session Header --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Attendance Session</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Date: {{ \Carbon\Carbon::parse($session->date)->format('M d, Y') }} |
                            Time: {{ $session->start_time }}{{ $session->end_time ? ' - ' . $session->end_time : '' }} |
                            Status:
                            @if($session->status === 'active')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Closed</span>
                            @endif
                        </p>
                    </div>
                    @if($session->status === 'active')
                        <div class="flex items-center space-x-3">
                            <form action="{{ route('teacher.attendance.close-session', $session) }}" method="POST"
                                x-data @submit.prevent="if(confirm('Are you sure you want to close this session?')) $el.submit()">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium py-2 px-4 rounded-lg">
                                    Close Session
                                </button>
                            </form>
                            <form action="{{ route('teacher.attendance.regenerate-qr', ['classSection' => $classSection, 'session' => $session]) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium py-2 px-4 rounded-lg">
                                    Regenerate QR
                                </button>
                            </form>
                            <a href="{{ route('teacher.attendance.show-qr', $session) }}"
                                target="_blank"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 px-4 rounded-lg">
                                Show QR Code
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- QR Code Section (Collapsible) --}}
            @if($session->status === 'active')
                <div class="bg-white shadow-sm sm:rounded-lg p-6" x-show="showQR" x-transition>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">QR Code for Check-in</h3>
                        <button @click="showQR = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="bg-white p-4 border-2 border-gray-200 rounded-lg">
                            {!! $qrSvg !!}
                        </div>
                        <p class="mt-3 text-sm text-gray-500">Token: <span class="font-mono">{{ $session->qr_token }}</span></p>
                        <p class="mt-1 text-xs text-gray-400">QR code refreshes every 30 seconds</p>
                    </div>
                </div>
            @endif

            {{-- Toggle QR Code Button --}}
            @if($session->status === 'active')
                <div class="flex justify-center">
                    <button
                        @click="showQR = !showQR"
                        class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium py-2 px-4 rounded-lg transition">
                        <span x-text="showQR ? 'Hide QR Code' : 'Show QR Code'"></span>
                    </button>
                </div>
            @endif

            {{-- Bulk Actions --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center space-x-3 mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Attendance Roster</h3>
                    @if($session->status === 'active')
                        <div class="flex items-center space-x-2 ml-auto">
                            <span class="text-sm text-gray-500">Bulk Actions:</span>
                            <button @click="bulkMark('present')" class="bg-green-500 hover:bg-green-600 text-white text-xs font-medium py-1 px-3 rounded">
                                Mark All Present
                            </button>
                            <button @click="bulkMark('absent')" class="bg-red-500 hover:bg-red-600 text-white text-xs font-medium py-1 px-3 rounded">
                                Mark All Absent
                            </button>
                            <button @click="applySelected()" class="bg-indigo-500 hover:bg-indigo-600 text-white text-xs font-medium py-1 px-3 rounded">
                                Apply Selected
                            </button>
                        </div>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                @if($session->status === 'active')
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">
                                        <input type="checkbox" x-model="selectAll" @change="toggleSelectAll" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    </th>
                                @endif
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Code</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                @if($session->status === 'active')
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($enrolledStudents as $student)
                                <tr class="hover:bg-gray-50" x-data="studentRow({{ $student->id }}, '{{ $student->currentRecord->status ?? 'absent' }}', '{{ $student->currentRecord->method ?? 'manual' }}')">
                                    @if($session->status === 'active')
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" x-model="selected" value="{{ $student->id }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                    @endif
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $student->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $student->student_code }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($session->status === 'active')
                                            <select x-model="status" @change="markChanged = true"
                                                class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                                <option value="present" :class="{ 'bg-green-50': status === 'present' }">Present</option>
                                                <option value="absent">Absent</option>
                                                <option value="late">Late</option>
                                                <option value="excused">Excused</option>
                                            </select>
                                        @else
                                            @php $st = $student->currentRecord->status ?? 'absent'; @endphp
                                            @if($st === 'present')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Present</span>
                                            @elseif($st === 'absent')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Absent</span>
                                            @elseif($st === 'late')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Late</span>
                                            @elseif($st === 'excused')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Excused</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php $method = $student->currentRecord->method ?? 'manual'; @endphp
                                        @if($method === 'qr')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">QR</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Manual</span>
                                        @endif
                                    </td>
                                    @if($session->status === 'active')
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <button @click="saveStatus()" :disabled="!markChanged || saving"
                                                class="text-indigo-600 hover:text-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                                <span x-show="!saving">Save</span>
                                                <span x-show="saving" class="text-gray-400">Saving...</span>
                                            </button>
                                            <span x-show="saved" class="text-green-600 text-xs ml-2">Saved!</span>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Summary Bar --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Summary</h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-green-600">{{ $stats['present'] ?? 0 }}</p>
                        <p class="text-sm text-gray-500">Present</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-red-600">{{ $stats['absent'] ?? 0 }}</p>
                        <p class="text-sm text-gray-500">Absent</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-yellow-600">{{ $stats['late'] ?? 0 }}</p>
                        <p class="text-sm text-gray-500">Late</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-blue-600">{{ $stats['excused'] ?? 0 }}</p>
                        <p class="text-sm text-gray-500">Excused</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-indigo-600">{{ $stats['rate'] ?? 0 }}%</p>
                        <p class="text-sm text-gray-500">Rate</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        function attendanceSession() {
            return {
                showQR: {{ $session->status === 'active' ? 'true' : 'false' }},
                selectAll: false,
                selected: [],

                toggleSelectAll() {
                    const checkboxes = document.querySelectorAll('input[type="checkbox"][value]');
                    checkboxes.forEach(cb => {
                        if (this.selectAll) {
                            this.selected.push(parseInt(cb.value));
                        } else {
                            this.selected = [];
                        }
                    });
                },

                bulkMark(status) {
                    document.querySelectorAll('[x-data="studentRow()"]').forEach(el => {
                        const component = Alpine.$data(el);
                        if (component) {
                            component.status = status;
                            component.markChanged = true;
                            component.saveStatus();
                        }
                    });
                },

                applySelected() {
                    document.querySelectorAll('input[type="checkbox"]:checked').forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        if (row) {
                            const component = Alpine.$data(row);
                            if (component && component.markChanged) {
                                component.saveStatus();
                            }
                        }
                    });
                }
            }
        }

        function studentRow(studentId, currentStatus, currentMethod) {
            return {
                studentId: studentId,
                status: currentStatus,
                method: currentMethod,
                markChanged: false,
                saving: false,
                saved: false,
                selected: false,

                async saveStatus() {
                    this.saving = true;
                    this.saved = false;
                    try {
                        const response = await fetch('{{ route("teacher.attendance.mark", $session) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                student_id: this.studentId,
                                status: this.status,
                                method: 'manual'
                            })
                        });

                        if (response.ok) {
                            this.markChanged = false;
                            this.saved = true;
                            this.method = 'manual';
                            setTimeout(() => { this.saved = false; }, 2000);
                        }
                    } catch (error) {
                        console.error('Error saving attendance:', error);
                        alert('Failed to save attendance. Please try again.');
                    } finally {
                        this.saving = false;
                    }
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
