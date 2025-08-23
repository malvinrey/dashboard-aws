import axios from "axios";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

/**
 * Echo preps the application for broadcasting, websocket and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// ========================================================================
// BLOK KODE BARU DIMULAI DI SINI
// ========================================================================

import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "reverb", // Gunakan 'reverb' agar lebih jelas dan konsisten
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: parseInt(import.meta.env.VITE_REVERB_PORT),
    wssPort: parseInt(import.meta.env.VITE_REVERB_PORT),
    forceTLS: false, // Paksa menggunakan WS (non-secure) untuk development
    enabledTransports: ["ws"], // Hanya gunakan WS, bukan WSS
    disableStats: true, // Matikan stats untuk mengurangi noise
});

// ========================================================================
// BLOK KODE BARU BERAKHIR DI SINI
// ========================================================================
