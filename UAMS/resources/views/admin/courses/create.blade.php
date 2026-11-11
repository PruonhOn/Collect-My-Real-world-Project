<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Course</h2>
            <a href="{{ route('admin.courses.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to Courses</a>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('admin.courses.store') }}">
                @csrf

                <div class="space-y-6">
                    <div>
                        <x-input-label for="name" :value="__('Course Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="code" :value="__('Course Code')" />
                        <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code')" required />
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="program_id" :value="__('Program')" />
                        <select id="program_id" name="program_id" class="mt-1 block w-full border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm" required>
                            <option value="">Select Program</option>
                            @foreach($programs as $program)
                                <option value="{{ $program->id }}" {{ old('program_id', request()->input('program_id')) == $program->id ? 'selected' : '' }}>{{ $program->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('program_id')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="credits" :value="__('Credits')" />
                            <x-text-input id="credits" name="credits" type="number" class="mt-1 block w-full" :value="old('credits')" min="1" max="6" required />
                            <x-input-error :messages="$errors->get('credits')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="semester" :value="__('Semester')" />
                            <x-text-input id="semester" name="semester" type="text" class="mt-1 block w-full" :value="old('semester')" placeholder="e.g. 1st" />
                            <x-input-error :messages="$errors->get('semester')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center justify-end mt-6 space-x-3">
                    <a href="{{ route('admin.courses.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">Cancel</a>
                    <x-primary-button>{{ __('Create Course') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
