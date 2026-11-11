<x-app-layout :title="__('messages.edit_profile')" :active="'profile'">
    <div class="max-w-3xl mx-auto space-y-6">

        @if (session('success'))
            <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-card>
            <h2 class="text-lg font-bold text-gray-900 mb-6">{{ __('messages.personal_info') }}</h2>
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <x-input name="name" :label="__('messages.full_name')" :value="$user->name" required />
                    <x-input name="phone" :label="__('messages.phone')" :value="$user->phone" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.email') }}</label>
                    <input type="email" value="{{ $user->email }}" disabled class="w-full rounded-xl bg-gray-50 border-gray-300 text-sm text-gray-500">
                </div>

                <div class="flex justify-end">
                    <x-button type="primary" submit>{{ __('messages.update') }}</x-button>
                </div>
            </form>
        </x-card>

        <x-card>
            <h2 class="text-lg font-bold text-gray-900 mb-6">{{ __('messages.change_password') }}</h2>
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <x-input name="current_password" type="password" :label="__('messages.current_password')" />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <x-input name="new_password" type="password" :label="__('messages.new_password')" />
                    <x-input name="new_password_confirmation" type="password" :label="__('messages.confirm_password')" />
                </div>

                <div class="flex justify-end">
                    <x-button type="primary" submit>{{ __('messages.change_password') }}</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
