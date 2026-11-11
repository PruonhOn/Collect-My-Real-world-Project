<x-app-layout :title="__('messages.browse_courses')" :active="'student.courses.index'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.browse_courses') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ __('messages.tagline') }}</p>
        </div>
        <a href="{{ route('student.courses.mine') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition">
            {{ __('messages.my_courses') }}
        </a>
    </div>

    <x-card :padding="false">
        <div class="p-4 border-b border-gray-100">
            <form method="GET" action="{{ route('student.courses.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.search') }}..." class="rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm">
                <select name="category_id" class="rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm bg-white">
                    <option value="">{{ __('messages.all') }} {{ __('messages.categories') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <select name="age_group" class="rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm bg-white">
                    <option value="">{{ __('messages.all') }} {{ __('messages.age_group') }}</option>
                    @foreach ($ageGroups as $key => $label)
                        <option value="{{ $key }}" @selected(request('age_group') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">{{ __('messages.search') }}</button>
                    <a href="{{ route('student.courses.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition text-center">{{ __('messages.reset') }}</a>
                </div>
            </form>
        </div>
    </x-card>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
        @forelse ($courses as $course)
            <x-card :padding="false" class="overflow-hidden">
                @if ($course->thumbnail)
                    <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="{{ $course->title }}" class="h-40 w-full object-cover">
                @else
                    <div class="h-40 w-full bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
                        <span class="text-4xl font-bold text-white/80">{{ mb_substr($course->title, 0, 1) }}</span>
                    </div>
                @endif
                <div class="p-5">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs text-gray-500">{{ $course->course_code }}</span>
                        <x-badge color="primary">{{ $course->category?->name ?? __('messages.no_data') }}</x-badge>
                    </div>
                    <h3 class="font-semibold text-gray-800 line-clamp-2">{{ $course->title }}</h3>
                    <p class="text-sm text-gray-500 line-clamp-2 mt-2">{{ $course->description }}</p>

                    <div class="flex items-center justify-between mt-4 text-sm">
                        <div class="text-gray-500">
                            {{ $course->enrollments_count }} {{ __('messages.students') }}
                        </div>
                        @if ($course->price > 0)
                            <span class="font-semibold text-primary-600">${{ number_format($course->price, 2) }}</span>
                        @else
                            <span class="font-semibold text-green-600">{{ __('messages.free') }}</span>
                        @endif
                    </div>

                    <div class="mt-4">
                        @if (in_array($course->id, $enrolledIds))
                            <div class="w-full text-center px-4 py-2.5 rounded-xl text-sm font-semibold bg-green-100 text-green-700">{{ __('messages.enrolled') }}</div>
                        @else
                            <a href="{{ route('student.courses.show', $course) }}" class="block text-center px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">{{ __('messages.details') }}</a>
                        @endif
                    </div>
                </div>
            </x-card>
        @empty
            <div class="col-span-full text-center py-16 text-gray-500">{{ __('messages.no_records') }}</div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $courses->links() }}
    </div>
</x-app-layout>