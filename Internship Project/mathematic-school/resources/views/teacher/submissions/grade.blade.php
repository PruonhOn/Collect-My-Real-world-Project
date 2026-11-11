<x-app-layout :title="__('messages.grade') . ' ' . __('messages.submission')" :active="'teacher.submissions'">
    @if ($errors->any())
        <div class="rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-700 mb-6">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.grade') }}</h1>
        <a href="{{ route('teacher.submissions.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card>
                <h3 class="font-semibold text-gray-800 mb-4">{{ $submission->assignment?->title }}</h3>
                <div class="bg-gray-50 rounded-xl p-5 whitespace-pre-line text-gray-700 text-sm leading-relaxed">
                    {{ $submission->content ?? __('messages.no_content') }}
                </div>
                @if ($submission->file_path)
                    <a href="{{ asset('storage/' . $submission->file_path) }}" target="_blank" class="inline-flex items-center gap-2 mt-4 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-50 text-primary-700 border border-primary-200 hover:bg-primary-100 transition">
                        {{ __('messages.download_file') }}
                    </a>
                @endif
            </x-card>

            <x-card>
                <h3 class="font-semibold text-gray-800 mb-4">{{ __('messages.submission_details') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.student') }}</dt><dd class="font-medium">{{ $submission->student?->full_name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.course') }}</dt><dd class="font-medium">{{ $submission->assignment?->course?->title }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.submitted_at') }}</dt><dd class="font-medium">{{ $submission->submitted_at?->format('d/m/Y H:i') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.total_marks') }}</dt><dd class="font-medium">{{ $submission->assignment?->total_marks }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.current_grade') }}</dt><dd class="font-medium">{{ $submission->grade ?? __('messages.not_graded') }}</dd></div>
                </dl>
            </x-card>
        </div>

        <div>
            <form method="POST" action="{{ route('teacher.submissions.update-grade', $submission) }}" class="space-y-6">
                @csrf
                @method('PUT')
                <x-card>
                    <h3 class="font-semibold text-gray-800 mb-4">{{ __('messages.grading') }}</h3>
                    <div class="space-y-5">
                        <x-input name="grade" :label="__('messages.grade')" type="number" step="0.5" min="0" :value="old('grade', $submission->grade)" required />
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.feedback') }}</label>
                            <textarea name="feedback" rows="5" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500">{{ old('feedback', $submission->feedback) }}</textarea>
                        </div>
                        <x-button type="primary" :submit="true">{{ __('messages.save_grade') }}</x-button>
                    </div>
                </x-card>
            </form>
        </div>
    </div>
</x-app-layout>