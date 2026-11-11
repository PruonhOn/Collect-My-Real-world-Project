<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-lg">
            <div class="flex flex-col items-center mb-8">
                <div class="w-20 h-20 rounded-2xl bg-primary-600 flex items-center justify-center text-white text-3xl font-bold shadow-lg mb-3">M&S</div>
                <h1 class="text-2xl font-bold text-gray-900 text-center">{{ __('messages.learning_system') }}</h1>
                <p class="text-gray-500 text-center mt-1">{{ __('messages.school_name') }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-xl font-bold text-gray-800 mb-6">{{ __('messages.register') }}</h2>

                @if ($errors->any())
                    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                        <ul class="list-disc pl-4 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.role') }}</label>
                        <select id="role" name="role" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="student" @selected(old('role', 'student') === 'student')>{{ __('messages.students') }}</option>
                            <option value="parent" @selected(old('role') === 'parent')>{{ __('messages.parents') }}</option>
                        </select>
                        @error('role')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.full_name') }}</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.email') }}</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.phone') }}</label>
                        <input id="phone" type="text" name="phone" value="{{ old('phone') }}" autocomplete="tel"
                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.password') }}</label>
                        <input id="password" type="password" name="password" required autocomplete="new-password"
                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.confirm_password') }}</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>

                    <button type="submit"
                        class="w-full rounded-xl bg-primary-600 text-white py-3 font-semibold hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition">
                        {{ __('messages.register') }}
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-gray-600">
                    {{ __('messages.already_registered') }}
                    <a href="{{ route('login') }}" class="text-primary-600 font-medium hover:underline">{{ __('messages.login') }}</a>
                </p>
            </div>
        </div>
    </div>
</x-guest-layout>
