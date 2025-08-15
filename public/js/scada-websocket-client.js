/**
 * SCADA WebSocket Client untuk komunikasi real-time dengan Soketi
 */
class ScadaWebSocketClient {
    constructor(config = {}) {
        this.config = {
            serverUrl: config.serverUrl || "ws://127.0.0.1:6001",
            appKey: config.appKey || "your_app_key_here",
            appId: config.appId || "12345",
            cluster: config.cluster || "mt1",
            encrypted: config.encrypted || false,
            channel: config.channel || "scada-data",
            onConnect: config.onConnect || (() => {}),
            onMessage: config.onMessage || (() => {}),
            onError: config.onError || (() => {}),
            onDisconnect: config.onDisconnect || (() => {}),
            autoReconnect: config.autoReconnect !== false,
            reconnectInterval: config.reconnectInterval || 5000,
            maxReconnectAttempts: config.maxReconnectAttempts || 10,
        };

        this.connection = null;
        this.channels = new Map();
        this.reconnectAttempts = 0;
        this.isConnecting = false;
        this.subscribedChannels = new Set();

        // Initialize Pusher
        this.initializePusher();
    }

    /**
     * Initialize Pusher client
     */
    initializePusher() {
        if (typeof Pusher === "undefined") {
            console.error(
                "Pusher library not loaded. Please include pusher-js in your HTML."
            );
            return;
        }

        // Parse server URL untuk mendapatkan host dan port
        const url = new URL(this.config.serverUrl);
        const host = url.hostname;
        const port = url.port || (url.protocol === "wss:" ? "443" : "80");

        this.pusher = new Pusher(this.config.appKey, {
            cluster: this.config.cluster,
            encrypted: this.config.encrypted,
            wsHost: host,
            wsPort: parseInt(port),
            forceTLS: false,
            enabledTransports: ["ws", "wss"],
            disableStats: true,
            authEndpoint: "/broadcasting/auth",
        });

        // Bind events
        this.pusher.connection.bind("connected", () => {
            console.log("Connected to WebSocket server");
            this.reconnectAttempts = 0;
            this.config.onConnect();
        });

        this.pusher.connection.bind("disconnected", () => {
            console.log("Disconnected from WebSocket server");
            this.config.onDisconnect();

            if (
                this.config.autoReconnect &&
                this.reconnectAttempts < this.config.maxReconnectAttempts
            ) {
                this.scheduleReconnect();
            }
        });

        this.pusher.connection.bind("error", (error) => {
            console.error("WebSocket connection error:", error);
            this.config.onError(error);
        });
    }

    /**
     * Connect to WebSocket server
     */
    connect() {
        if (this.isConnecting) return;

        this.isConnecting = true;
        console.log("Connecting to WebSocket server...");

        // Pusher automatically connects when initialized
        this.isConnecting = false;
    }

    /**
     * Disconnect from WebSocket server
     */
    disconnect() {
        if (this.pusher) {
            this.pusher.disconnect();
        }
    }

    /**
     * Subscribe to a channel
     */
    subscribe(channelName) {
        if (this.subscribedChannels.has(channelName)) {
            console.log(`Already subscribed to channel: ${channelName}`);
            return;
        }

        try {
            const channel = this.pusher.subscribe(channelName);
            this.channels.set(channelName, channel);
            this.subscribedChannels.add(channelName);

            // Listen for events
            channel.bind("scada.data.received", (data) => {
                this.config.onMessage(data);
            });

            channel.bind("scada.batch.received", (data) => {
                this.config.onMessage(data);
            });

            channel.bind("scada.aggregated.received", (data) => {
                this.config.onMessage(data);
            });

            console.log(`Subscribed to channel: ${channelName}`);
        } catch (error) {
            console.error(
                `Error subscribing to channel ${channelName}:`,
                error
            );
        }
    }

    /**
     * Unsubscribe from a channel
     */
    unsubscribe(channelName) {
        if (this.pusher && this.subscribedChannels.has(channelName)) {
            this.pusher.unsubscribe(channelName);
            this.channels.delete(channelName);
            this.subscribedChannels.delete(channelName);
            console.log(`Unsubscribed from channel: ${channelName}`);
        }
    }

    /**
     * Send message to server (if bidirectional communication is needed)
     */
    send(message) {
        // Note: Pusher doesn't support direct client-to-server messaging
        // This would require additional server-side implementation
        console.warn(
            "Direct messaging not supported with Pusher. Use HTTP API instead."
        );
    }

    /**
     * Get connection status
     */
    getConnectionState() {
        return this.pusher ? this.pusher.connection.state : "disconnected";
    }

    /**
     * Check if connected
     */
    isConnected() {
        return this.pusher && this.pusher.connection.state === "connected";
    }

    /**
     * Get subscribed channels
     */
    getSubscribedChannels() {
        return Array.from(this.subscribedChannels);
    }

    /**
     * Schedule reconnection
     */
    scheduleReconnect() {
        this.reconnectAttempts++;
        console.log(
            `Scheduling reconnection attempt ${this.reconnectAttempts} in ${this.config.reconnectInterval}ms`
        );

        setTimeout(() => {
            if (!this.isConnected()) {
                this.connect();
            }
        }, this.config.reconnectInterval);
    }

    /**
     * Update configuration
     */
    updateConfig(newConfig) {
        this.config = { ...this.config, ...newConfig };

        // Reinitialize if server URL changed
        if (
            newConfig.serverUrl &&
            newConfig.serverUrl !== this.config.serverUrl
        ) {
            this.disconnect();
            this.initializePusher();
        }
    }
}

/**
 * Laravel Echo integration (alternative approach)
 */
class ScadaEchoClient {
    constructor(config = {}) {
        this.config = {
            serverUrl: config.serverUrl || "http://127.0.0.1:6001",
            appKey: config.appKey || "your_app_key_here",
            appId: config.appId || "12345",
            cluster: config.cluster || "mt1",
            encrypted: config.encrypted || false,
            onConnect: config.onConnect || (() => {}),
            onMessage: config.onMessage || (() => {}),
            onError: config.onError || (() => {}),
            onDisconnect: config.onDisconnect || (() => {}),
        };

        this.initializeEcho();
    }

    /**
     * Initialize Laravel Echo compatibility layer
     */
    initializeEcho() {
        // Pastikan pustaka Pusher dan Echo tersedia
        if (typeof Pusher === "undefined" || typeof Echo === "undefined") {
            console.error(
                "Pusher.js or Laravel Echo is not loaded. Cannot initialize Echo."
            );
            return;
        }

        // Hindari inisialisasi ganda
        if (window.Echo && window.Echo.socketId()) {
            console.log("Laravel Echo is already initialized.");
            return;
        }

        // Hapus layer kompatibilitas lama jika ada
        if (window.Echo && !window.Echo.socketId) {
            delete window.Echo;
        }

        // Inisialisasi instance Laravel Echo yang sesungguhnya
        try {
            window.Echo = new Echo({
                broadcaster: "pusher",
                key: this.config.appKey || "scada_dashboard_key_2024",
                cluster: this.config.cluster || "mt1",
                wsHost: this.config.host || "127.0.0.1",
                wsPort: this.config.port || 6001,
                wssPort: this.config.port || 6001,
                forceTLS: false,
                enabledTransports: ["ws", "wss"],
                disableStats: true,
            });

            console.log(
                "Real Laravel Echo instance initialized for Livewire compatibility."
            );

            // Panggil onConnect setelah Echo siap
            window.Echo.connector.pusher.connection.bind("connected", () => {
                console.log("Echo connected successfully!");
                if (typeof this.config.onConnect === "function") {
                    this.config.onConnect();
                }
            });
        } catch (e) {
            console.error("Failed to initialize Laravel Echo:", e);
        }
    }

    /**
     * Listen to channel
     */
    listen(channelName, eventName, callback) {
        if (!window.Echo) {
            console.error("Laravel Echo not initialized");
            return;
        }

        window.Echo.channel(channelName).listen(eventName, callback);
    }

    /**
     * Listen to private channel
     */
    listenPrivate(channelName, eventName, callback) {
        if (!window.Echo) {
            console.error("Laravel Echo not initialized");
            return;
        }

        window.Echo.private(channelName).listen(eventName, callback);
    }

    /**
     * Leave channel
     */
    leave(channelName) {
        if (window.Echo) {
            window.Echo.leaveChannel(channelName);
        }
    }
}

// Export for use in other modules
if (typeof module !== "undefined" && module.exports) {
    module.exports = { ScadaWebSocketClient, ScadaEchoClient };
}
