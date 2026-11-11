<x-app-layout :title="__('messages.add') . ' ' . __('messages.enrollment')" :active="'admin.enrollments'">
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
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.add') }} {{ __('messages.enrollment') }}</h1>
        <a href="{{ route('admin.enrollments.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <form method="POST" action="{{ route('admin.enrollments.store') }}">
        @csrf
        <x-card>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-select name="student_id" :label="__('messages.student')" :required="true">
                    <option value="">{{ __('messages.select') }}</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->full_name }}</option>
                    @endforeach
                </x-select>
                <x-select name="course_id" :label="__('messages.course')" :required="true">
                    <option value="">{{ __('messages.select') }}</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected(old('course_id') == $course->id)>{{ $course->title }}</option>
                    @endforeach
                </x-select>
                <x-select name="status" :label="__('messages.status')" :required="true">
                    <option value="active" @selected(old('status') === 'active')>{{ __('messages.active') }}</option>
                    <option value="completed" @selected(old('status') === 'completed')>{{ __('messages.completed') }}</option>
                    <option value="dropped" @selected(old('status') === 'dropped')>{{ __('messages.dropped') }}</option>
                    <option value="pending" @selected(old('status') === 'pending')>{{ __('messages.pending') }}</option>
                </x-select>
            </div>

            <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-100">
                <x-button type="primary" :submit="true">{{ __('messages.save') }}</x-button>
                <a href="{{ route('admin.enrollments.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition">{{ __('messages.cancel') }}</a>
            </div>
        </x-card>
    </form>
</x-app-layout>