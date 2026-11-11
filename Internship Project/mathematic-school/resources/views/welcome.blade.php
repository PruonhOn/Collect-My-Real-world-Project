<x-guest-layout>
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-primary-600 via-primary-500 to-blue-600"></div>
        <div class="relative min-h-screen flex flex-col">
            <header class="flex items-center justify-between px-6 py-5 sm:px-10">
                <div class="flex items-center gap-3 text-white">
                    <div class="w-11 h-11 rounded-xl bg-white/15 flex items-center justify-center text-white font-bold text-lg backdrop-blur">M&S</div>
                    <div class="leading-tight">
                        <div class="font-bold">{{ __('messages.school_name') }}</div>
                        <div class="text-sm text-white/80">{{ __('messages.tagline') }}</div>
                    </div>
                </div>
                <nav class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-lg text-white bg-white/20 hover:bg-white/30 font-medium">{{ __('messages.dashboard') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg text-white hover:bg-white/10 font-medium">{{ __('messages.login') }}</a>
                        <a href="{{ route('register') }}" class="px-5 py-2 rounded-lg bg-white text-primary-700 font-semibold hover:bg-white/90">{{ __('messages.register') }}</a>
                    @endauth
                </nav>
            </header>

            <main class="flex-1 flex flex-col items-center justify-center px-6 text-center text-white py-16">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold max-w-3xl leading-tight">{{ __('messages.school_name') }}</h1>
                <p class="text-lg sm:text-xl text-white/90 mt-4 max-w-2xl">{{ __('messages.learning_system') }}</p>

                <div class="flex flex-wrap gap-4 mt-10 justify-center">
                    <a href="{{ route('register') }}" class="px-8 py-4 rounded-2xl bg-white text-primary-700 font-bold text-lg shadow-lg hover:bg-white/90 transition">{{ __('messages.register') }}</a>
                    <a href="{{ route('certificates.verify') }}" class="px-8 py-4 rounded-2xl bg-white/15 text-white font-bold text-lg backdrop-blur hover:bg-white/25 transition">{{ __('messages.verify_certificate') }}</a>
                </div>
            </main>

            <footer class="py-4 px-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-sm text-white/80">
                <span>{{ __('messages.school_name') }} — {{ __('messages.tagline') }}</span>
                <a href="{{ route('certificates.verify') }}" class="hover:underline">{{ __('messages.verify_certificate') }}</a>
            </footer>
        </div>
    </div>
</x-guest-layout>
