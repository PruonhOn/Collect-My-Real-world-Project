<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Class Section</h2>
            <a href="{{ route('admin.class-sections.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to Sections</a>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('admin.class-sections.update', $classSection) }}">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <div>
                        <x-input-label for="name" :value="__('Section Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $classSection->name)" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="course_id" :value="__('Course')" />
                            <select id="course_id" name="course_id" class="mt-1 block w-full border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm" required>
                                <option value="">Select Course</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ old('course_id', $classSection->course_id) == $course->id ? 'selected' : '' }}>{{ $course->code }} - {{ $course->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('course_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="teacher_id" :value="__('Teacher')" />
                            <select id="teacher_id" name="teacher_id" class="mt-1 block w-full border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm" required>
                                <option value="">Select Teacher</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ old('teacher_id', $classSection->teacher_id) == $teacher->id ? 'selected' : '' }}>{{ $teacher->user->name ?? $teacher->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('teacher_id')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="term_id" :value="__('Academic Term')" />
                        <select id="term_id" name="term_id" class="mt-1 block w-full border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm" required>
                            <option value="">Select Term</option>
                            @foreach($terms as $term)
                                <option value="{{ $term->id }}" {{ old('term_id', $classSection->term_id) == $term->id ? 'selected' : '' }}>{{ $term->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('term_id')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="capacity" :value="__('Capacity')" />
                            <x-text-input id="capacity" name="capacity" type="number" class="mt-1 block w-full" :value="old('capacity', $classSection->capacity)" min="1" required />
                            <x-input-error :messages="$errors->get('capacity')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="schedule" :value="__('Schedule')" />
                            <x-text-input id="schedule" name="schedule" type="text" class="mt-1 block w-full" :value="old('schedule', $classSection->schedule)" />
                            <x-input-error :messages="$errors->get('schedule')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $classSection->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-600">Active</span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end mt-6 space-x-3">
                    <a href="{{ route('admin.class-sections.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">Cancel</a>
                    <x-primary-button>{{ __('Update Section') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
