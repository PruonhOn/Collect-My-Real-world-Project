<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-md">
            <div class="flex flex-col items-center mb-8">
                <div class="w-20 h-20 rounded-2xl bg-primary-600 flex items-center justify-center text-white text-3xl font-bold shadow-lg mb-3">M&S</div>
                <h1 class="text-2xl font-bold text-gray-900 text-center">{{ __('messages.learning_system') }}</h1>
                <p class="text-gray-500 text-center mt-1">{{ __('messages.school_name') }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-xl font-bold text-gray-800 mb-2">{{ __('messages.forgot_password') }}</h2>
                <p class="text-sm text-gray-500 mb-6">បញ្ចូលអ៊ីមែល ហើយយើងនឹងផ្ញើតំណកំណត់ពាក្យសម្ងាត់ឡើងវិញឱ្យអ្នក។</p>

                @if ($errors->any())
                    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                        <ul class="list-disc pl-4 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('status'))
                    <div class="mb-4 rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.email') }}</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>

                    <button type="submit"
                        class="w-full rounded-xl bg-primary-600 text-white py-3 font-semibold hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition">
                        {{ __('messages.send_reset_link') }}
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-gray-600">
                    <a href="{{ route('login') }}" class="text-primary-600 font-medium hover:underline">{{ __('messages.back') }}</a>
                </p>
            </div>
        </div>
    </div>
</x-guest-layout>
