<x-app-layout :title="$assignment->title" :active="'student.assignments'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <a href="{{ route('student.assignments.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← {{ __('messages.back') }}</a>
        <a href="{{ route('student.courses.show', $assignment->course) }}" class="text-sm text-primary-600 font-semibold hover:underline">{{ $assignment->course->title }}</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card>
                <h1 class="text-2xl font-bold text-gray-800 mb-3">{{ $assignment->title }}</h1>
                <p class="text-gray-600 whitespace-pre-line">{{ $assignment->description }}</p>
                <div class="flex items-center gap-4 mt-4 text-sm text-gray-500">
                    <span>{{ __('messages.total_marks') }}: {{ $assignment->total_marks }}</span>
                    <span>{{ __('messages.deadline') }}: {{ $assignment->due_date?->format('d/m/Y H:i') }}</span>
                </div>
            </x-card>

            <x-card>
                <h3 class="font-semibold text-lg mb-4">
                    {{ $submission ? __('messages.view') . ' ' . __('messages.submission') : __('messages.submit_work') }}
                </h3>

                @if ($submission)
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                            <span class="text-sm font-medium">{{ __('messages.submission') }}</span>
                            <x-badge :color="$submission->status === 'graded' ? 'green' : 'yellow'">
                                {{ $submission->status === 'graded' ? __('messages.graded') : __('messages.pending') }}
                            </x-badge>
                        </div>
                        @if ($submission->file_path)
                            <a href="{{ asset('storage/' . $submission->file_path) }}" target="_blank" class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition">
                                <span class="text-sm font-medium">{{ $submission->file_path }}</span>
                                <span class="text-xs text-primary-600 font-semibold">{{ __('messages.download') }}</span>
                            </a>
                        @endif
                        @if ($submission->comments)
                            <div class="p-3 rounded-xl bg-gray-50 text-sm text-gray-600">{{ $submission->comments }}</div>
                        @endif
                        @if ($submission->grade !== null)
                            <div class="p-4 rounded-xl bg-blue-50 border border-blue-100">
                                <div class="text-sm text-blue-700 font-semibold">{{ __('messages.grade') }}: {{ $submission->grade }} / {{ $assignment->total_marks }}</div>
                                @if ($submission->feedback)
                                    <div class="text-sm text-blue-600 mt-2">{{ $submission->feedback }}</div>
                                @endif
                            </div>
                        @endif
                    </div>
                @else
                    <form method="POST" action="{{ route('student.assignments.submit', $assignment) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.upload_work') }}</label>
                                <input type="file" name="file" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                                @error('file')
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.comments') }}</label>
                                <textarea name="comments" rows="3" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"></textarea>
                            </div>
                            <div>
                                <button type="submit" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">
                                    {{ __('messages.submit') }}
                                </button>
                            </div>
                        </div>
                    </form>
                @endif
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card>
                <h3 class="font-semibold text-lg mb-3">{{ __('messages.details') }}</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('messages.courses') }}</dt>
                        <dd class="font-medium">{{ $assignment->course->title }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('messages.deadline') }}</dt>
                        <dd class="font-medium">{{ $assignment->due_date?->format('d/m/Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('messages.marks') }}</dt>
                        <dd class="font-medium">{{ $assignment->total_marks }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>
</x-app-layout>