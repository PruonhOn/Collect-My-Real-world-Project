<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Program</h2>
            <a href="{{ route('admin.programs.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to Programs</a>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('admin.programs.store') }}">
                @csrf

                <div class="space-y-6">
                    <div>
                        <x-input-label for="name" :value="__('Program Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="code" :value="__('Program Code')" />
                        <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code')" required />
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="department_id" :value="__('Department')" />
                        <select id="department_id" name="department_id" class="mt-1 block w-full border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm" required>
                            <option value="">Select Department</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ old('department_id', request()->input('department_id')) == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="level" :value="__('Level')" />
                            <select id="level" name="level" class="mt-1 block w-full border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm">
                                <option value="">Select Level</option>
                                <option value="bachelor" {{ old('level') === 'bachelor' ? 'selected' : '' }}>Bachelor's</option>
                                <option value="master" {{ old('level') === 'master' ? 'selected' : '' }}>Master's</option>
                                <option value="phd" {{ old('level') === 'phd' ? 'selected' : '' }}>PhD</option>
                                <option value="diploma" {{ old('level') === 'diploma' ? 'selected' : '' }}>Diploma</option>
                            </select>
                            <x-input-error :messages="$errors->get('level')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="duration" :value="__('Duration (years)')" />
                            <x-text-input id="duration" name="duration" type="number" class="mt-1 block w-full" :value="old('duration')" min="1" max="10" />
                            <x-input-error :messages="$errors->get('duration')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center justify-end mt-6 space-x-3">
                    <a href="{{ route('admin.programs.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">Cancel</a>
                    <x-primary-button>{{ __('Create Program') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
