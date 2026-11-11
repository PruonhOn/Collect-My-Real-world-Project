<x-app-layout :title="__('messages.reports')" :active="'admin.reports'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.reports') }}</h1>
        <a href="{{ route('admin.reports.export', ['report' => $reportType] + request()->query()) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            {{ __('messages.export_csv') }}
        </a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        <x-card>
            <div class="text-sm text-gray-500">{{ __('messages.students') }}</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['students'] }}</div>
        </x-card>
        <x-card>
            <div class="text-sm text-gray-500">{{ __('messages.courses') }}</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['courses'] }}</div>
        </x-card>
        <x-card>
            <div class="text-sm text-gray-500">{{ __('messages.enrollments') }}</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['enrollments'] }}</div>
        </x-card>
        <x-card>
            <div class="text-sm text-gray-500">{{ __('messages.teachers') }}</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['teachers'] }}</div>
        </x-card>
        <x-card class="col-span-2 md:col-span-1">
            <div class="text-sm text-gray-500">{{ __('messages.revenue') }}</div>
            <div class="text-2xl font-bold text-green-700 mt-1">${{ number_format($stats['revenue'], 2) }}</div>
        </x-card>
    </div>

    <x-card class="mb-6">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-select name="report" :label="__('messages.report_type')">
                <option value="students" @selected($reportType === 'students')>{{ __('messages.students') }}</option>
                <option value="enrollments" @selected($reportType === 'enrollments')>{{ __('messages.enrollments') }}</option>
                <option value="payments" @selected($reportType === 'payments')>{{ __('messages.payments') }}</option>
                <option value="courses" @selected($reportType === 'courses')>{{ __('messages.courses') }}</option>
            </x-select>
            <x-input name="from_date" :label="__('messages.from_date')" type="date" :value="request('from_date')" />
            <x-input name="to_date" :label="__('messages.to_date')" type="date" :value="request('to_date')" />
            <div class="flex items-end gap-2">
                <x-button type="primary" :submit="true">{{ __('messages.filter') }}</x-button>
                <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center px-4 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold hover:bg-gray-50">{{ __('messages.reset') }}</a>
            </div>
        </form>
    </x-card>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        @if ($reportType === 'students')
                            <th class="text-left px-4 py-3 font-medium text-gray-500">ID</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.full_name') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.gender') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.age') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.phone') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.status') }}</th>
                        @elseif ($reportType === 'enrollments')
                            <th class="text-left px-4 py-3 font-medium text-gray-500">ID</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.student') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.course') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.status') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.progress_percent') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.enrollment_date') }}</th>
                        @elseif ($reportType === 'payments')
                            <th class="text-left px-4 py-3 font-medium text-gray-500">ID</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.student') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.course') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.amount') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.method') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.status') }}</th>
                        @else
                            <th class="text-left px-4 py-3 font-medium text-gray-500">ID</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.course_code') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.course_title') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.category') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.price') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.status') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.students') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $row)
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            @if ($reportType === 'students')
                                <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $row->student_id }}</td>
                                <td class="px-4 py-3 font-medium">{{ $row->full_name }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $row->gender === 'male' ? __('messages.male') : __('messages.female') }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $row->age }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $row->phone }}</td>
                                <td class="px-4 py-3"><x-badge :color="$row->status === 'active' ? 'green' : 'red'">{{ student_status_label($row->status) }}</x-badge></td>
                            @elseif ($reportType === 'enrollments')
                                <td class="px-4 py-3 text-gray-500">{{ $row->id }}</td>
                                <td class="px-4 py-3 font-medium">{{ $row->student?->full_name }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $row->course?->title }}</td>
                                <td class="px-4 py-3"><x-badge color="gray">{{ enrollment_status_label($row->status) }}</x-badge></td>
                                <td class="px-4 py-3 text-gray-500">{{ round($row->completion_percentage) }}%</td>
                                <td class="px-4 py-3 text-gray-500">{{ $row->enrolled_at?->format('d/m/Y') }}</td>
                            @elseif ($reportType === 'payments')
                                <td class="px-4 py-3 text-gray-500">{{ $row->id }}</td>
                                <td class="px-4 py-3 font-medium">{{ $row->student?->full_name }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $row->course?->title }}</td>
                                <td class="px-4 py-3 font-semibold">${{ number_format($row->amount, 2) }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $row->method ?? '—' }}</td>
                                <td class="px-4 py-3"><x-badge :color="$row->status === 'paid' ? 'green' : 'yellow'">{{ payment_status_label($row->status) }}</x-badge></td>
                            @else
                                <td class="px-4 py-3 text-gray-500">{{ $row->id }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $row->course_code }}</td>
                                <td class="px-4 py-3 font-medium">{{ $row->title }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $row->category?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $row->price > 0 ? '$' . number_format($row->price, 2) : __('messages.free') }}</td>
                                <td class="px-4 py-3"><x-badge color="gray">{{ course_status_label($row->status) }}</x-badge></td>
                                <td class="px-4 py-3 text-gray-500">{{ $row->enrollments_count }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">{{ __('messages.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $data->links() }}
        </div>
    </x-card>
</x-app-layout>