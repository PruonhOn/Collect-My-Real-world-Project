<x-app-layout :title="__('messages.details') . ' ' . __('messages.students')" :active="'admin.students'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ $student->full_name }}</h1>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.students.edit', $student) }}" class="px-4 py-2 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">{{ __('messages.edit') }}</a>
            <a href="{{ route('admin.students.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <x-card>
            <h3 class="font-semibold text-gray-800 mb-4">{{ __('messages.personal_info') }}</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">{{ __('messages.student_id') }}</span><span>{{ $student->student_id }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('messages.full_name') }}</span><span>{{ $student->full_name }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('messages.gender') }}</span><span>{{ $student->gender === 'male' ? __('messages.male') : __('messages.female') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('messages.date_of_birth') }}</span><span>{{ $student->date_of_birth?->format('d/m/Y') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('messages.age') }}</span><span>{{ $student->age }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('messages.phone') }}</span><span>{{ $student->phone }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('messages.address') }}</span><span>{{ $student->address }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('messages.status') }}</span>
                    <x-badge :color="$student->status === 'active' ? 'green' : 'red'">{{ student_status_label($student->status) }}</x-badge>
                </div>
            </div>
        </x-card>

        <x-card>
            <h3 class="font-semibold text-gray-800 mb-4">{{ __('messages.guardian') }}</h3>
            @forelse ($student->parents as $parent)
                <div class="p-3 bg-gray-50 rounded-xl mb-2 text-sm">
                    <div class="font-medium">{{ $parent->full_name }}</div>
                    <div class="text-gray-500">{{ $parent->phone }} | {{ $parent->pivot->relation ?? '' }}</div>
                </div>
            @empty
                <p class="text-sm text-gray-500">{{ __('messages.no_data') }}</p>
            @endforelse
        </x-card>

        <x-card>
            <h3 class="font-semibold text-gray-800 mb-4">{{ __('messages.enrollments') }}</h3>
            @forelse ($student->enrollments as $enrollment)
                <div class="p-3 bg-gray-50 rounded-xl mb-2 text-sm">
                    <div class="font-medium">{{ $enrollment->course->title ?? '-' }}</div>
                    <div class="flex justify-between mt-1">
                        <x-badge :color="$enrollment->status === 'active' ? 'green' : 'blue'">{{ enrollment_status_label($enrollment->status) }}</x-badge>
                        <span class="text-gray-500">{{ $enrollment->completion_percentage }}%</span>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">{{ __('messages.no_data') }}</p>
            @endforelse
        </x-card>
    </div>

    @if ($student->certificates->isNotEmpty())
        <x-card class="mt-6">
            <h3 class="font-semibold text-gray-800 mb-4">{{ __('messages.certificates') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($student->certificates as $cert)
                    <div class="p-3 bg-gray-50 rounded-xl text-sm">
                        <div class="font-medium">{{ $cert->course->title ?? '-' }}</div>
                        <div class="text-gray-500">{{ $cert->certificate_id }}</div>
                        <div class="text-gray-500">{{ $cert->issued_date?->format('d/m/Y') }}</div>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif
</x-app-layout>
