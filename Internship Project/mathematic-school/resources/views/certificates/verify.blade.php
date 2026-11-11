<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-2xl">
            <div class="mb-8 text-center">
                <div class="w-20 h-20 mx-auto rounded-2xl bg-primary-600 flex items-center justify-center text-white text-3xl font-bold shadow-lg mb-3">M&S</div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.verify_certificate') }}</h1>
                <p class="text-gray-500 mt-1">{{ __('messages.school_name') }}</p>
            </div>

            <x-card>
                <form method="GET" action="{{ route('certificates.verify') }}" class="flex gap-3">
                    <input type="text" name="certificate_id" value="{{ request('certificate_id') }}" required
                        placeholder="{{ __('messages.cert_id') }}"
                        class="flex-1 rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <x-button type="primary" submit>{{ __('messages.verify_certificate') }}</x-button>
                </form>

                @if(isset($valid))
                    <div class="mt-6">
                        @if($valid)
                            <div class="rounded-xl bg-green-50 border border-green-200 p-6">
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                    <div>
                                        <div class="font-bold text-green-800">{{ __('messages.certificate_valid') }}</div>
                                        <div class="text-sm text-green-700">{{ $certificate->certificate_id }}</div>
                                    </div>
                                </div>
                                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <dt class="text-gray-500">{{ __('messages.issued_to') }}</dt>
                                        <dd class="font-semibold text-gray-900">{{ $certificate->student->full_name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">{{ __('messages.course_title') }}</dt>
                                        <dd class="font-semibold text-gray-900">{{ $certificate->course->title }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">{{ __('messages.issued_date') }}</dt>
                                        <dd class="font-semibold text-gray-900">{{ $certificate->issued_date->format('d/m/Y') }}</dd>
                                    </div>
                                </dl>
                            </div>
                        @else
                            <div class="rounded-xl bg-red-50 border border-red-200 p-6">
                                <div class="flex items-center gap-3">
                                    <span class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </span>
                                    <div class="font-bold text-red-800">{{ __('messages.certificate_invalid') }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </x-card>
        </div>
    </div>
</x-guest-layout>
