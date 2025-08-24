import Echo from 'laravel-echo';

const key      = import.meta.env.VITE_REVERB_APP_KEY;
const wsHost   = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
// Di belakang ada Caddy/Cloud Run dengan TLS; browser bicara 80/443
const wsPort   = 80;
const wssPort  = 443;
const forceTLS = window.location.protocol === 'https:';
const csrf     = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

export const echo = new Echo({
    broadcaster: 'reverb',
    key,
    wsHost,
    wsPort,
    wssPort,
    forceTLS,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
    withCredentials: true,
    auth: { headers: { 'X-CSRF-TOKEN': csrf } },
});

try {
    echo.connector.connection.bind('state_change', s => console.log('[Reverb] state:', s));
    echo.connector.connection.bind('error', e => console.error('[Reverb] error:', e));
} catch {}
