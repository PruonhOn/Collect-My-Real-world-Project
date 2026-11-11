<x-app-layout :title="__('messages.dashboard')" :active="'admin.dashboard'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
        <x-card>
            <div class="text-sm text-gray-500">{{ __('messages.total_students') }}</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ $totalStudents }}</div>
        </x-card>
        <x-card>
            <div class="text-sm text-gray-500">{{ __('messages.total_parents') }}</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ $totalParents }}</div>
        </x-card>
        <x-card>
            <div class="text-sm text-gray-500">{{ __('messages.total_teachers') }}</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ $totalTeachers }}</div>
        </x-card>
        <x-card>
            <div class="text-sm text-gray-500">{{ __('messages.total_courses') }}</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ $totalCourses }}</div>
        </x-card>
        <x-card>
            <div class="text-sm text-gray-500">{{ __('messages.total_enrollments') }}</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ $totalEnrollments }}</div>
        </x-card>
        <x-card>
            <div class="text-sm text-gray-500">{{ __('messages.total_revenue') }}</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ __('messages.price_currency') }}{{ number_format($totalRevenue, 2) }}</div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-card>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('messages.recent_enrollments') }}</h3>
            @if ($recentEnrollments->isEmpty())
                <p class="text-sm text-gray-500">{{ __('messages.no_data') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-left py-2 font-medium text-gray-500">{{ __('messages.students') }}</th>
                                <th class="text-left py-2 font-medium text-gray-500">{{ __('messages.courses') }}</th>
                                <th class="text-left py-2 font-medium text-gray-500">{{ __('messages.status') }}</th>
                                <th class="text-left py-2 font-medium text-gray-500">{{ __('messages.date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentEnrollments as $enrollment)
                                <tr class="border-b border-gray-50">
                                    <td class="py-2">{{ $enrollment->student->full_name ?? '-' }}</td>
                                    <td class="py-2">{{ $enrollment->course->title ?? '-' }}</td>
                                    <td class="py-2">
                                        <x-badge :color="$enrollment->status === 'active' ? 'green' : ($enrollment->status === 'completed' ? 'blue' : 'gray')">
                                            {{ enrollment_status_label($enrollment->status) }}
                                        </x-badge>
                                    </td>
                                    <td class="py-2 text-gray-500">{{ $enrollment->enrolled_at?->format('d/m/Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <x-card>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('messages.top_courses') }}</h3>
            @if ($topCourses->isEmpty())
                <p class="text-sm text-gray-500">{{ __('messages.no_data') }}</p>
            @else
                <div class="space-y-3">
                    @foreach ($topCourses as $course)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                            <div>
                                <div class="font-medium text-gray-800 text-sm">{{ $course->title }}</div>
                                <div class="text-xs text-gray-500">{{ $course->course_code }}</div>
                            </div>
                            <div class="text-sm font-semibold text-primary-600">{{ $course->enrollments_count }} {{ __('messages.enrollments') }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
