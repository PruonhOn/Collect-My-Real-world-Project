import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// ----------------------------------------------------------------
// Laravel Echo — Real-time via Reverb
// ----------------------------------------------------------------
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// Subscribe to global work-order updates (for real-time badge counts)
window.Echo.channel('work-orders')
    .listen('WorkOrderCreated', () => window.Livewire?.dispatch('$refresh'))
    .listen('WorkOrderUpdated', () => window.Livewire?.dispatch('$refresh'));
