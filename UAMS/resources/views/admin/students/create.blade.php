<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Student</h2>
            <a href="{{ route('admin.students.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to Students</a>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('admin.students.store') }}">
                @csrf

                <div class="space-y-6">
                    <div>
                        <x-input-label for="name" :value="__('Full Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="password" :value="__('Password')" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="student_code" :value="__('Student ID')" />
                            <x-text-input id="student_code" name="student_code" type="text" class="mt-1 block w-full" :value="old('student_code')" required />
                            <x-input-error :messages="$errors->get('student_code')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="year" :value="__('Year Level')" />
                            <select id="year" name="year" class="mt-1 block w-full border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm" required>
                                @for($y = 1; $y <= 8; $y++)
                                    <option value="{{ $y }}" {{ old('year', 1) == $y ? 'selected' : '' }}>Year {{ $y }}</option>
                                @endfor
                            </select>
                            <x-input-error :messages="$errors->get('year')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="department_id" :value="__('Department')" />
                            <select id="department_id" name="department_id" class="mt-1 block w-full border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm" required>
                                <option value="">Select Department</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="program_id" :value="__('Program')" />
                            <select id="program_id" name="program_id" class="mt-1 block w-full border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm" required>
                                <option value="">Select Program</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}" {{ old('program_id') == $program->id ? 'selected' : '' }}>{{ $program->code }} - {{ $program->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('program_id')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end mt-6 space-x-3">
                    <a href="{{ route('admin.students.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">Cancel</a>
                    <x-primary-button>{{ __('Create Student') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
