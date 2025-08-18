import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const wsHost  = import.meta.env.VITE_REVERB_HOST ?? '127.0.0.1';
const wsPort  = Number(import.meta.env.VITE_REVERB_PORT ?? 8080);
const scheme  = import.meta.env.VITE_REVERB_SCHEME ?? 'http';
const forceTLS = scheme === 'https';
const csrf    = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

export const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost,
    wsPort,
    wssPort: wsPort,
    forceTLS,
    enabledTransports: ['ws', 'wss'],

    authEndpoint: '/broadcasting/auth',
    withCredentials: true,
    auth: { headers: { 'X-CSRF-TOKEN': csrf } },
});

try {
    echo.connector.pusher.connection.bind('state_change', s => console.log('[Reverb] state:', s));
    echo.connector.pusher.connection.bind('error', e => console.error('[Reverb] error:', e));
} catch {}
