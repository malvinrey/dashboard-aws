/**
 * SCADA WebSocket Client untuk komunikasi real-time dengan Laravel Reverb.
 * Versi ini menggunakan Laravel Echo & Pusher.js untuk kompatibilitas penuh
 * dengan server Reverb dan Livewire.
 */
class ScadaWebSocketClient {
    constructor(config = {}) {
        this.config = {
            host: config.host || "127.0.0.1",
            port: config.port || 8080,
            appKey: config.appKey || "scada_dashboard_key_2024",
            cluster: config.cluster || "mt1",
            forceTLS: config.forceTLS || false,
            onConnect: config.onConnect || (() => {}),
            onMessage: config.onMessage || (() => {}),
            onError: config.onError || (() => {}),
            onDisconnect: config.onDisconnect || (() => {}),
        };

        this.initializeEcho(); // Langsung inisialisasi Echo
    }

    /**
     * Menginisialisasi instance Laravel Echo yang sesungguhnya.
     */
    initializeEcho() {
        if (typeof Pusher === "undefined" || typeof Echo === "undefined") {
            console.error(
                "Pusher.js atau Laravel Echo tidak termuat. Inisialisasi dibatalkan."
            );
            return;
        }

        // Cek jika Echo sudah ada dan berfungsi, jangan buat lagi.
        if (window.Echo && typeof window.Echo.socketId === "function") {
            console.log("Laravel Echo sudah diinisialisasi untuk Reverb.");
            this.bindEvents(); // Cukup ikat event-nya saja
            return;
        }

        console.log(
            "Menginisialisasi instance Laravel Echo baru untuk Reverb..."
        );
        try {
            window.Echo = new Echo({
                broadcaster: "reverb", // Gunakan 'reverb' untuk kompatibilitas
                key: this.config.appKey,
                wsHost: this.config.host,
                wsPort: this.config.port,
                wssPort: this.config.port,
                forceTLS: this.config.forceTLS,
                enabledTransports: ["ws", "wss"],
                disableStats: true,
                // Reverb specific options
                useTLS: this.config.forceTLS,
                encrypted: false,
                // Connection options
                timeout: 30000,
                keepAlive: true,
                keepAliveInterval: 25000,
            });

            this.bindEvents();
            console.log(
                "Instance Laravel Echo berhasil diinisialisasi untuk Reverb."
            );
        } catch (e) {
            console.error("Gagal menginisialisasi Laravel Echo:", e);
            if (typeof this.config.onError === "function") {
                this.config.onError(e);
            }
        }
    }

    /**
     * Mengikat (bind) event-event koneksi ke instance Pusher di dalam Echo.
     */
    bindEvents() {
        const pusher = window.Echo.connector.pusher;

        pusher.connection.bind("connected", () => {
            console.log(
                "✅ Berhasil terhubung ke server WebSocket via Echo/Pusher."
            );
            if (typeof this.config.onConnect === "function") {
                this.config.onConnect();
            }
        });

        pusher.connection.bind("disconnected", () => {
            console.warn("Koneksi WebSocket terputus.");
            if (typeof this.config.onDisconnect === "function") {
                this.config.onDisconnect();
            }
        });

        pusher.connection.bind("error", (error) => {
            console.error("Error koneksi WebSocket:", error);
            if (typeof this.config.onError === "function") {
                this.config.onError(error);
            }
        });
    }

    /**
     * Berlangganan (subscribe) ke sebuah channel dan mendengarkan event.
     * @param {string} channelName - Nama channel.
     * @param {string} eventName - Nama event.
     * @param {function} callback - Fungsi yang akan dipanggil saat event diterima.
     */
    subscribe(channelName, eventName, callback) {
        if (!window.Echo) {
            console.error("Echo belum diinisialisasi. Tidak bisa subscribe.");
            return;
        }

        console.log(
            `Subscribe ke channel: ${channelName}, event: ${eventName}`
        );
        window.Echo.channel(channelName).listen(eventName, callback);
    }

    /**
     * Memutuskan koneksi WebSocket.
     */
    disconnect() {
        if (window.Echo) {
            window.Echo.disconnect();
        }
    }
}

// Export untuk ES6 modules
export default ScadaWebSocketClient;
