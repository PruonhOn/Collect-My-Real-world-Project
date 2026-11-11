<x-app-layout :title="__('messages.certificates')" :active="'admin.certificates'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.certificates') }}</h1>
        <a href="{{ route('admin.certificates.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">
            {{ __('messages.add') }} {{ __('messages.certificate') }}
        </a>
    </div>

    <x-card :padding="false">
        <div class="p-4 border-b border-gray-100">
            <form method="GET" action="{{ route('admin.certificates.index') }}" class="flex flex-col sm:flex-row gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.search') }}..." class="flex-1 rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm">
                <button type="submit" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">{{ __('messages.search') }}</button>
                <a href="{{ route('admin.certificates.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition text-center">{{ __('messages.reset') }}</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.certificate_id') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.student') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.course') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.issued_date') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($certificates as $certificate)
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $certificate->certificate_id }}</td>
                            <td class="px-4 py-3 font-medium">{{ $certificate->student?->full_name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $certificate->course?->title }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $certificate->issued_date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.certificates.show', $certificate) }}" class="text-primary-600 hover:text-primary-800 text-xs font-medium">{{ __('messages.view') }}</a>
                                    <form method="POST" action="{{ route('admin.certificates.destroy', $certificate) }}" onsubmit="return confirm('{{ __('messages.are_you_sure') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">{{ __('messages.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('messages.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $certificates->links() }}
        </div>
    </x-card>
</x-app-layout>