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

        this.pusher = new Pusher(this.config.appKey, {
            cluster: this.config.cluster,
            encrypted: this.config.encrypted,
            wsHost: this.config.serverUrl.replace(/^ws:\/\//, "").split(":")[0],
            wsPort: parseInt(this.config.serverUrl.split(":")[2]) || 6001,
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
     * Initialize Laravel Echo
     */
    initializeEcho() {
        if (typeof Echo === "undefined") {
            console.error(
                "Laravel Echo not loaded. Please include laravel-echo in your HTML."
            );
            return;
        }

        window.Echo = new Echo({
            broadcaster: "pusher",
            key: this.config.appKey,
            cluster: this.config.cluster,
            encrypted: this.config.encrypted,
            wsHost: this.config.serverUrl
                .replace(/^https?:\/\//, "")
                .split(":")[0],
            wsPort: parseInt(this.config.serverUrl.split(":")[2]) || 6001,
            forceTLS: false,
            enabledTransports: ["ws", "wss"],
            disableStats: true,
            authEndpoint: "/broadcasting/auth",
        });

        // Bind connection events
        window.Echo.connector.pusher.connection.bind("connected", () => {
            console.log("Connected via Laravel Echo");
            this.config.onConnect();
        });

        window.Echo.connector.pusher.connection.bind("disconnected", () => {
            console.log("Disconnected via Laravel Echo");
            this.config.onDisconnect();
        });

        window.Echo.connector.pusher.connection.bind("error", (error) => {
            console.error("Echo connection error:", error);
            this.config.onError(error);
        });
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
