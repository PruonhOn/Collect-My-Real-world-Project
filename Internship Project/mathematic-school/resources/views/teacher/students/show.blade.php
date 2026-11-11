<x-app-layout :title="__('messages.student')" :active="'teacher.students'">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-800">{{ $student->full_name }}</h1>
            <x-badge :color="$student->status === 'active' ? 'green' : 'red'">{{ student_status_label($student->status) }}</x-badge>
        </div>
        <a href="{{ route('teacher.courses.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card :padding="false">
                <div class="p-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">{{ __('messages.enrollments') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.course') }}</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.status') }}</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.progress_percent') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($student->enrollments as $enrollment)
                                <tr class="border-t border-gray-100">
                                    <td class="px-4 py-3 font-medium">{{ $enrollment->course?->title }}</td>
                                    <td class="px-4 py-3"><x-badge color="gray">{{ enrollment_status_label($enrollment->status) }}</x-badge></td>
                                    <td class="px-4 py-3 text-gray-500">{{ round($enrollment->completion_percentage) }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center text-gray-500">{{ __('messages.no_records') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            <x-card :padding="false">
                <div class="p-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">{{ __('messages.quiz_attempts') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.quiz') }}</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.score') }}</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.result') }}</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($student->quizAttempts as $attempt)
                                <tr class="border-t border-gray-100">
                                    <td class="px-4 py-3 font-medium">{{ $attempt->quiz?->title }}</td>
                                    <td class="px-4 py-3">{{ $attempt->score }}</td>
                                    <td class="px-4 py-3">
                                        <x-badge :color="$attempt->passed ? 'green' : 'red'">{{ $attempt->passed ? __('messages.passed') : __('messages.failed') }}</x-badge>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ $attempt->submitted_at?->format('d/m/Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">{{ __('messages.no_records') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            <x-card :padding="false">
                <div class="p-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">{{ __('messages.assignments') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.assignment') }}</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.grade') }}</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($student->assignmentSubmissions as $submission)
                                <tr class="border-t border-gray-100">
                                    <td class="px-4 py-3 font-medium">{{ $submission->assignment?->title }}</td>
                                    <td class="px-4 py-3">{{ $submission->grade ?? '—' }}</td>
                                    <td class="px-4 py-3"><x-badge :color="$submission->status === 'graded' ? 'green' : 'yellow'">{{ $submission->status === 'graded' ? __('messages.graded') : __('messages.submitted') }}</x-badge></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center text-gray-500">{{ __('messages.no_records') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card>
                <h3 class="font-semibold text-gray-800 mb-4">{{ __('messages.details') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.student_id') }}</dt><dd class="font-medium font-mono text-xs">{{ $student->student_id }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.age') }}</dt><dd class="font-medium">{{ $student->age }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.gender') }}</dt><dd class="font-medium">{{ $student->gender === 'male' ? __('messages.male') : __('messages.female') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.phone') }}</dt><dd class="font-medium">{{ $student->phone ?? '—' }}</dd></div>
                </dl>
            </x-card>
        </div>
    </div>
</x-app-layout>