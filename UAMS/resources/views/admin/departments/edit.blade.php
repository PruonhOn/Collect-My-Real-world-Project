<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Department</h2>
            <a href="{{ route('admin.departments.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to Departments</a>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('admin.departments.update', $department) }}">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <div>
                        <x-input-label for="name" :value="__('Department Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $department->name)" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="code" :value="__('Department Code')" />
                        <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code', $department->code)" required />
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="faculty_id" :value="__('Faculty')" />
                        <select id="faculty_id" name="faculty_id" class="mt-1 block w-full border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm" required>
                            <option value="">Select Faculty</option>
                            @foreach($faculties as $faculty)
                                <option value="{{ $faculty->id }}" {{ old('faculty_id', $department->faculty_id) == $faculty->id ? 'selected' : '' }}>{{ $faculty->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('faculty_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm">{{ old('description', $department->description) }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center justify-end mt-6 space-x-3">
                    <a href="{{ route('admin.departments.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">Cancel</a>
                    <x-primary-button>{{ __('Update Department') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
