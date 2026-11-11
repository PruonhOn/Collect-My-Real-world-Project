<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>QR Code - {{ $classSection->course->name ?? 'N/A' }} - {{ $classSection->name }}</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { margin: 0; padding: 0; background: #f9fafb; font-family: 'Inter', system-ui, sans-serif; }
    </style>
</head>
<body x-data="qrRefresh()" x-init="init()" class="relative min-h-screen bg-slate-950 overflow-hidden">
    <div class="pointer-events-none absolute -top-32 -left-32 w-[480px] h-[480px] rounded-full bg-indigo-600/25 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-40 -right-24 w-[520px] h-[520px] rounded-full bg-violet-600/20 blur-3xl"></div>

    <div class="relative min-h-screen flex flex-col items-center justify-center p-8">

        {{-- Session Info --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-white">
                {{ $classSection->course->name ?? 'N/A' }} · {{ $classSection->name }}
            </h1>
            <p class="mt-2 text-lg text-slate-400">
                {{ \Carbon\Carbon::parse($session->session_date)->format('l, F j, Y') }}
            </p>
            <p class="text-lg text-slate-500">
                {{ $session->start_time }}{{ $session->end_time ? ' - ' . $session->end_time : '' }}
            </p>
        </div>

        {{-- QR Code --}}
        <div class="bg-white p-8 rounded-3xl shadow-2xl shadow-indigo-500/20" x-show="!refreshing">
            <div class="qr-container">
                {!! \SimplesoftwareIO\QrCode\Facades\QrCode::format('svg')->size(400)->generate(url('/student/qr-checkin/' . $session->qr_token)) !!}
            </div>
        </div>

        {{-- Refreshing indicator --}}
        <div x-cloak x-show="refreshing" class="bg-white p-8 rounded-3xl flex items-center justify-center" style="width: 464px; height: 464px;">
            <div class="text-center">
                <svg class="animate-spin h-12 w-12 text-indigo-600 mx-auto" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-3 text-slate-500">Refreshing QR code...</p>
            </div>
        </div>

        {{-- Token & Instructions --}}
        <div class="mt-7 text-center">
            <p class="text-sm text-slate-500">
                Token: <span class="font-mono font-bold text-indigo-300">{{ $session->qr_token }}</span>
            </p>
            <p class="mt-2 text-xl font-semibold text-white">
                Scan this code to mark your attendance
            </p>
            <p class="mt-1 text-sm text-slate-500">
                Auto-refreshes in <span x-text="countdown" class="font-bold text-indigo-300"></span> seconds
            </p>
        </div>

        {{-- Status --}}
        <div class="mt-5">
            <span class="px-3.5 py-1.5 inline-flex items-center gap-2 text-sm font-semibold rounded-full bg-emerald-500/10 border border-emerald-400/30 text-emerald-300">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Session Active
            </span>
        </div>

    </div>

    <script>
        function qrRefresh() {
            return {
                countdown: 30,
                refreshing: false,
                interval: null,
                countdownInterval: null,

                init() {
                    this.countdownInterval = setInterval(() => {
                        this.countdown--;
                        if (this.countdown <= 0) {
                            this.refreshQR();
                        }
                    }, 1000);
                },

                async refreshQR() {
                    this.refreshing = true;
                    this.countdown = 30;

                    try {
                        const response = await fetch(window.location.href, {
                            headers: {
                                'Accept': 'text/html'
                            }
                        });

                        if (response.ok) {
                            window.location.reload();
                        }
                    } catch (error) {
                        console.error('Error refreshing QR:', error);
                        this.refreshing = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
