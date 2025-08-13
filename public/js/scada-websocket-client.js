/**
 * SCADA WebSocket Client untuk menangani koneksi real-time
 * Implementasi yang robust dengan auto-reconnect, heartbeat, dan error handling
 */
class ScadaWebSocketClient {
    constructor(options = {}) {
        this.options = {
            url: options.url || "ws://localhost:6001/app/scada-app",
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
        this.onMessage = null;
        this.onConnect = null;
        this.onDisconnect = null;
        this.onError = null;

        // Connection state
        this.connectionState = "disconnected";
        this.lastMessageTime = 0;

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

            if (this.onConnect) {
                this.onConnect();
            }
        };

        this.ws.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                this.lastMessageTime = Date.now();

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
            this.handleDisconnect(event);
        };

        this.ws.onerror = (error) => {
            console.error("WebSocket error:", error);
            this.handleConnectionError(error);
        };
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
}
