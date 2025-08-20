/**
 * SCADA WebSocket Client untuk komunikasi real-time dengan Soketi
 * Implementasi berdasarkan WEBSOCKET_IMPLEMENTATION_GUIDE
 */
class ScadaWebSocketClient {
    constructor(options = {}) {
        this.options = {
            url:
                options.url ||
                `ws://${window.location.hostname}:6001/app/scada_dashboard_key_2024`,
            reconnectAttempts: options.reconnectAttempts || 10,
            reconnectDelay: options.reconnectDelay || 1000,
            maxReconnectDelay: options.maxReconnectDelay || 30000,
            heartbeatInterval: options.heartbeatInterval || 30000,
            ...options,
        };

        this.ws = null;
        this.reconnectAttempts = 0;
        this.reconnectTimer = null;
        this.heartbeatTimer = null;
        this.isConnecting = false;
        this.isConnected = false;

        // Event handlers
        this.onMessage = options.onMessage || (() => {});
        this.onConnect = options.onConnect || (() => {});
        this.onDisconnect = options.onDisconnect || (() => {});
        this.onError = options.onError || (() => {});

        // Connection state
        this.connectionState = "disconnected";
        this.lastMessageTime = 0;

        // --- NEW ---: Handlers for specific channels and events
        this.channelHandlers = new Map();
        this.subscribedChannels = new Set();

        // Start connection
        this.connect();
    }

    connect() {
        if (this.isConnecting || this.isConnected) return;

        this.isConnecting = true;
        this.connectionState = "connecting";

        console.log(`Connecting to WebSocket: ${this.options.url}`);

        try {
            this.ws = new WebSocket(this.options.url);
            this.setupEventHandlers();
        } catch (error) {
            console.error("Failed to create WebSocket connection:", error);
            this.handleConnectionError(error);
        }
    }

    setupEventHandlers() {
        this.ws.onopen = () => {
            console.log("WebSocket connection established");
            this.isConnecting = false;
            this.isConnected = true;
            this.connectionState = "connected";
            this.reconnectAttempts = 0;

            // Start heartbeat
            this.startHeartbeat();

            // Resubscribe to channels
            this.resubscribeChannels();

            if (this.onConnect) {
                this.onConnect();
            }
        };

        this.ws.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                this.lastMessageTime = Date.now();

                // --- NEW ---: Handle Pusher protocol messages
                if (data.event && data.event.startsWith("pusher:")) {
                    if (data.event === "pusher:connection_established") {
                        console.log(
                            "Soketi connection established successfully."
                        );
                    }
                    return; // Ignore other internal pusher events for now
                }

                // --- NEW ---: Route message to the correct handler
                if (data.channel && data.event) {
                    const channel = data.channel;
                    const eventName = data.event;
                    const handlerKey = `${channel}:${eventName}`;

                    if (this.channelHandlers.has(handlerKey)) {
                        // Parse the data property as it's often a JSON string
                        const eventData =
                            typeof data.data === "string"
                                ? JSON.parse(data.data)
                                : data.data;
                        this.channelHandlers.get(handlerKey)(eventData);
                    }
                }

                // Handle different message types
                this.handleMessage(data);

                if (this.onMessage) {
                    this.onMessage(data);
                }
            } catch (error) {
                console.error("Error parsing WebSocket message:", error);
            }
        };

        this.ws.onclose = (event) => {
            console.log(
                "WebSocket connection closed:",
                event.code,
                event.reason
            );
            this.isConnected = false;
            this.connectionState = "disconnected";
            this.stopHeartbeat();

            if (this.onDisconnect) {
                this.onDisconnect();
            }

            // Attempt to reconnect
            this.attemptReconnect();
        };

        this.ws.onerror = (error) => {
            console.error("WebSocket error:", error);
            this.handleConnectionError(error);
        };
    }

    // --- NEW ---: Method to subscribe to a channel
    subscribe(channelName, eventName, callback) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            // Store the handler for this channel/event combination
            const handlerKey = `${channelName}:${eventName}`;
            this.channelHandlers.set(handlerKey, callback);

            // Subscribe to channel if not already subscribed
            if (!this.subscribedChannels.has(channelName)) {
                this.ws.send(
                    JSON.stringify({
                        event: "pusher:subscribe",
                        data: {
                            channel: channelName,
                        },
                    })
                );
                this.subscribedChannels.add(channelName);
                console.log(`Subscribed to channel: ${channelName}`);
            }
        }
    }

    // --- NEW ---: Method to unsubscribe from a channel
    unsubscribe(channelName) {
        if (
            this.ws &&
            this.ws.readyState === WebSocket.OPEN &&
            this.subscribedChannels.has(channelName)
        ) {
            this.ws.send(
                JSON.stringify({
                    event: "pusher:unsubscribe",
                    data: {
                        channel: channelName,
                    },
                })
            );
            this.subscribedChannels.delete(channelName);

            // Remove all handlers for this channel
            for (const [key] of this.channelHandlers) {
                if (key.startsWith(`${channelName}:`)) {
                    this.channelHandlers.delete(key);
                }
            }
            console.log(`Unsubscribed from channel: ${channelName}`);
        }
    }

    handleMessage(data) {
        // Handle different message types from Soketi
        if (data.event && data.channel) {
            const channel = data.channel;
            const event = data.event;
            const payload = data.data;

            // Check if we have a handler for this channel and event
            const handlerKey = `${channel}:${event}`;
            if (this.channelHandlers.has(handlerKey)) {
                const handler = this.channelHandlers.get(handlerKey);
                handler(payload, data);
            }

            // Handle specific SCADA events
            if (event === "scada.data.received") {
                this.handleScadaData(payload);
            } else if (event === "scada.batch.received") {
                this.handleBatchData(payload);
            } else if (event === "scada.aggregated.received") {
                this.handleAggregatedData(payload);
            }
        }
    }

    handleScadaData(data) {
        console.log("SCADA data received:", data);
        // Emit custom event for SCADA data
        this.emit("scadaData", data);
    }

    handleBatchData(data) {
        console.log("SCADA batch data received:", data);
        this.emit("batchData", data);
    }

    handleAggregatedData(data) {
        console.log("SCADA aggregated data received:", data);
        this.emit("aggregatedData", data);
    }

    startHeartbeat() {
        this.heartbeatTimer = setInterval(() => {
            if (this.isConnected && this.ws.readyState === WebSocket.OPEN) {
                this.ws.send(
                    JSON.stringify({
                        type: "heartbeat",
                        timestamp: Date.now(),
                    })
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

    handleDisconnect(event) {
        this.isConnected = false;
        this.connectionState = "disconnected";

        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
            this.heartbeatTimer = null;
        }

        if (this.onDisconnect) {
            this.onDisconnect(event);
        }

        // Attempt reconnection
        this.scheduleReconnect();
    }

    handleConnectionError(error) {
        this.isConnecting = false;
        this.connectionState = "error";

        if (this.onError) {
            this.onError(error);
        }

        // Attempt reconnection
        this.scheduleReconnect();
    }

    scheduleReconnect() {
        if (this.reconnectAttempts >= this.options.reconnectAttempts) {
            console.error("Max reconnection attempts reached");
            this.connectionState = "failed";
            return;
        }

        const delay = Math.min(
            this.options.reconnectDelay * Math.pow(2, this.reconnectAttempts),
            this.options.maxReconnectDelay
        );

        console.log(
            `Scheduling reconnection attempt ${
                this.reconnectAttempts + 1
            } in ${delay}ms`
        );

        this.reconnectTimer = setTimeout(() => {
            this.reconnectAttempts++;
            this.connect();
        }, delay);
    }

    resubscribeChannels() {
        // Resubscribe to all channels after reconnection
        for (const channel of this.subscribedChannels) {
            this.subscribe(channel);
        }
    }

    send(data) {
        if (this.isConnected && this.ws.readyState === WebSocket.OPEN) {
            try {
                this.ws.send(JSON.stringify(data));
                return true;
            } catch (error) {
                console.error("Failed to send data:", error);
                return false;
            }
        } else {
            console.warn("WebSocket not connected, cannot send data");
            return false;
        }
    }

    disconnect() {
        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
            this.reconnectTimer = null;
        }

        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
            this.heartbeatTimer = null;
        }

        if (this.ws) {
            this.ws.close(1000, "Client disconnect");
            this.ws = null;
        }

        this.isConnecting = false;
        this.isConnected = false;
        this.connectionState = "disconnected";
    }

    // Event emitter functionality
    emit(eventName, data) {
        const event = new CustomEvent(eventName, { detail: data });
        window.dispatchEvent(event);
    }

    // Getters
    getConnectionState() {
        return this.connectionState;
    }

    isConnectionHealthy() {
        return (
            this.isConnected &&
            Date.now() - this.lastMessageTime <
                this.options.heartbeatInterval * 2
        );
    }

    getSubscribedChannels() {
        return Array.from(this.subscribedChannels);
    }
}

/**
 * Laravel Echo compatibility layer for existing code
 * This maintains backward compatibility while using native WebSocket
 */
class ScadaEchoClient {
    constructor(config = {}) {
        this.config = {
            host: config.host || window.location.hostname,
            port: config.port || 6001,
            appKey: config.appKey || "scada_dashboard_key_2024",
            cluster: config.cluster || "mt1",
            forceTLS: config.forceTLS || false,
            onConnect: config.onConnect || (() => {}),
            onMessage: config.onMessage || (() => {}),
            onError: config.onError || (() => {}),
            onDisconnect: config.onDisconnect || (() => {}),
        };

        // Create WebSocket client
        this.websocketClient = new ScadaWebSocketClient({
            url: `ws://${this.config.host}:${this.config.port}/app/${this.config.appKey}`,
            onConnect: this.config.onConnect,
            onMessage: this.config.onMessage,
            onError: this.config.onError,
            onDisconnect: this.config.onDisconnect,
        });

        // Create Echo-like interface for compatibility
        this.createEchoInterface();
    }

    // Add disconnect method to ScadaEchoClient
    disconnect() {
        if (this.websocketClient) {
            this.websocketClient.disconnect();
        }
        if (window.Echo) {
            delete window.Echo;
        }
        console.log("ScadaEchoClient disconnected");
    }

    createEchoInterface() {
        // Create window.Echo for Livewire compatibility
        window.Echo = {
            connector: {
                pusher: {
                    connection: {
                        state: "connected",
                        bind: (event, callback) => {
                            if (event === "connected") {
                                callback();
                            }
                        },
                    },
                },
            },
            socketId: () =>
                `ws_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
            channel: (channelName) => {
                return {
                    listen: (eventName, callback) => {
                        const handlerKey = `${channelName}:${eventName}`;
                        this.websocketClient.channelHandlers.set(
                            handlerKey,
                            callback
                        );

                        // Subscribe to channel if not already subscribed
                        if (
                            !this.websocketClient.subscribedChannels.has(
                                channelName
                            )
                        ) {
                            this.websocketClient.subscribe(
                                channelName,
                                eventName,
                                callback
                            );
                        }

                        return this;
                    },
                };
            },
            private: (channelName) => {
                return {
                    listen: (eventName, callback) => {
                        const handlerKey = `private-${channelName}:${eventName}`;
                        this.websocketClient.channelHandlers.set(
                            handlerKey,
                            callback
                        );

                        // Subscribe to private channel
                        this.websocketClient.subscribe(
                            `private-${channelName}`,
                            eventName,
                            callback
                        );

                        return this;
                    },
                };
            },
            leaveChannel: (channelName) => {
                this.websocketClient.unsubscribe(channelName);
            },
            disconnect: () => {
                this.websocketClient.disconnect();
            },
        };

        console.log("Echo compatibility layer created for Livewire");
    }
}

// Export for use in other modules
if (typeof module !== "undefined" && module.exports) {
    module.exports = { ScadaWebSocketClient, ScadaEchoClient };
}
