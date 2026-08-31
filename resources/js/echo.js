import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// 1. Read runtime configuration from meta tags (or fallback to Vite build env)
const metaKey = document.querySelector('meta[name="reverb-key"]')?.getAttribute('content');
const metaHost = document.querySelector('meta[name="reverb-host"]')?.getAttribute('content');
const metaPort = document.querySelector('meta[name="reverb-port"]')?.getAttribute('content');
const metaScheme = document.querySelector('meta[name="reverb-scheme"]')?.getAttribute('content');

const key = metaKey || import.meta.env.VITE_REVERB_APP_KEY || 'chatkey123456789';

// 2. Resolve WebSocket Host
// If metaHost / VITE_REVERB_HOST is explicitly configured (e.g. your-reverb-tunnel.loca.lt), use it.
// Otherwise, fallback to the current page hostname (e.g. 192.168.x.x, myapp.loca.lt, or localhost).
let host = metaHost || import.meta.env.VITE_REVERB_HOST;
if (!host || host === 'localhost' || host === '127.0.0.1') {
    host = window.location.hostname || 'localhost';
}

// 3. Resolve Scheme and Port
const isPageHttps = window.location.protocol === 'https:';
const scheme = metaScheme || import.meta.env.VITE_REVERB_SCHEME || (isPageHttps ? 'https' : 'http');
const isSecure = scheme === 'https' || isPageHttps;

// If connecting via HTTPS tunnel (localtunnel, ngrok), use standard port 443 for WSS
let port = metaPort || import.meta.env.VITE_REVERB_PORT;
if (isSecure) {
    port = 443;
} else if (!port) {
    port = 8080;
}

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: key,
    wsHost: host,
    wsPort: isSecure ? 443 : port,
    wssPort: 443,
    forceTLS: isSecure,
    enabledTransports: ['ws', 'wss'],
});
