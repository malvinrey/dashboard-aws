/**
 * SCADA WebSocket Client (Vanilla JS)
 * Implementasi ini fokus pada koneksi WebSocket standar yang stabil dengan
 * reconnect logic dan heartbeat.
 */
class ScadaWebSocketClient {
    constructor(options = {}) {
        this.options = {
            url: options.url || "ws://127.0.0.1:6001/",
            reconnectAttempts: options.reconnectAttempts || 10,
            reconnectDelay: options.reconnectDelay || 2000,
            heartbeatInterval: options.heartbeatInterval || 30000,
            ...options,
        };

        this.ws = null;
        this.reconnectAttempts = 0;
        this.reconnectTimer = null;
        this.heartbeatTimer = null;
        this.channelHandlers = new Map(); // Untuk menyimpan callback per channel

        // Bind event handlers agar 'this' merujuk ke instance class
        this.onOpen = this.onOpen.bind(this);
        this.onMessage = this.onMessage.bind(this);
        this.onClose = this.onClose.bind(this);
        this.onError = this.onError.bind(this);

        this.connect();
    }

    connect() {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            console.log("WebSocket is already connected.");
            return;
        }

        console.log(`Connecting to WebSocket: ${this.options.url}`);
        this.ws = new WebSocket(this.options.url);

        this.ws.onopen = this.onOpen;
        this.ws.onmessage = this.onMessage;
        this.ws.onclose = this.onClose;
        this.ws.onerror = this.onError;
    }

    onOpen(event) {
        console.log("✅ WebSocket connection established.");
        this.reconnectAttempts = 0;
        if (this.reconnectTimer) clearTimeout(this.reconnectTimer);

        // Kirim pesan subscribe untuk semua channel yang sudah terdaftar
        this.channelHandlers.forEach((_, channelKey) => {
            const [channelName] = channelKey.split(":");
            this.subscribe(channelName);
        });

        this.startHeartbeat();
        if (this.options.onConnect) this.options.onConnect(event);
    }

    onMessage(event) {
        try {
            const data = JSON.parse(event.data);

            // Logika untuk menangani pesan PUSHER dari SOKETI
            if (data.event && data.channel) {
                const channelKey = `${data.channel}:${data.event}`;
                if (this.channelHandlers.has(channelKey)) {
                    const payload = JSON.parse(data.data); // Data dari Pusher biasanya stringified JSON
                    this.channelHandlers.get(channelKey)(payload);
                }
            } else {
                // Fallback untuk pesan non-pusher
                if (this.options.onMessage) this.options.onMessage(data);
            }
        } catch (e) {
            console.error("Failed to parse WebSocket message:", e);
        }
    }

    onClose(event) {
        console.warn(`WebSocket connection closed: ${event.code}`);
        this.stopHeartbeat();
        if (this.options.onDisconnect) this.options.onDisconnect(event);
        this.scheduleReconnect();
    }

    onError(event) {
        console.error("WebSocket error:", event);
        if (this.options.onError) this.options.onError(event);
    }

    scheduleReconnect() {
        if (this.reconnectAttempts >= this.options.reconnectAttempts) {
            console.error("Max reconnect attempts reached. Giving up.");
            return;
        }
        this.reconnectAttempts++;
        const delay = Math.min(
            this.options.reconnectDelay * this.reconnectAttempts,
            30000
        );
        console.log(
            `Scheduling reconnection attempt ${this.reconnectAttempts} in ${delay}ms`
        );
        this.reconnectTimer = setTimeout(() => this.connect(), delay);
    }

    startHeartbeat() {
        this.stopHeartbeat(); // Hentikan timer lama jika ada
        this.heartbeatTimer = setInterval(() => {
            if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                // Soketi/Pusher mengharapkan ping dalam format tertentu
                this.ws.send(
                    JSON.stringify({ event: "pusher:ping", data: {} })
                );
            }
        }, this.options.heartbeatInterval);
    }

    stopHeartbeat() {
        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
            this.heartbeatTimer = null;
        }
    }

    // Metode untuk subscribe ke channel
    subscribe(channel, event, callback) {
        const channelKey = `${channel}:${event}`;
        this.channelHandlers.set(channelKey, callback);

        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(
                JSON.stringify({
                    event: "pusher:subscribe",
                    data: {
                        channel: channel,
                        auth: null,
                    },
                })
            );
            console.log(`Sent subscription request for channel: ${channel}`);
        }
    }

    disconnect() {
        this.stopHeartbeat();
        if (this.reconnectTimer) clearTimeout(this.reconnectTimer);
        if (this.ws) {
            this.ws.close();
        }
    }
}
