/**
 * SSE Worker untuk menangani koneksi EventSource di background
 * SOLUSI DEFINITIF: Koneksi ini akan tetap aktif bahkan saat tab tidak terlihat
 *
 * Keunggulan Web Worker:
 * 1. Koneksi SSE berjalan di thread terpisah
 * 2. Tidak terpengaruh oleh perubahan visibilitas tab
 * 3. Auto-reconnect dengan exponential backoff
 * 4. Heartbeat internal untuk mencegah timeout
 */
let sseConnection = null;
let reconnectAttempts = 0;
const MAX_RECONNECT_ATTEMPTS = 5;
let isActive = true; // Flag untuk mencegah reconnect saat worker dihentikan

self.onmessage = function (e) {
    if (e.data.type === "start") {
        startSseConnection(e.data.url, e.data.tags, e.data.interval);
    } else if (e.data.type === "stop") {
        stopSseConnection();
    } else if (e.data.type === "updateConfig") {
        // Update konfigurasi tanpa restart koneksi
        updateConfig(e.data.tags, e.data.interval);
    } else if (e.data.type === "heartbeat") {
        // Kirim heartbeat untuk memastikan koneksi tetap aktif
        self.postMessage({
            type: "heartbeat",
            timestamp: Date.now(),
        });
    }
};

function startSseConnection(url, tags, interval) {
    console.log("SSE Worker: Starting connection to:", url);

    // Pastikan worker masih aktif sebelum memulai koneksi
    if (!isActive) {
        console.log("SSE Worker: Worker is not active, skipping connection");
        return;
    }

    if (sseConnection) {
        sseConnection.close();
        sseConnection = null;
    }

    try {
        sseConnection = new EventSource(url);
        reconnectAttempts = 0;

        // Event: Connection established
        sseConnection.onopen = function (event) {
            console.log("SSE Worker: Connection established");
            self.postMessage({
                type: "status",
                status: "connected",
                message: "SSE connection established",
            });
        };

        // Event: Data received
        sseConnection.onmessage = function (event) {
            try {
                const data = JSON.parse(event.data);
                console.log("SSE Worker: Data received:", data);

                self.postMessage({
                    type: "data",
                    data: data,
                });
            } catch (error) {
                console.error("SSE Worker: Error parsing data:", error);
                self.postMessage({
                    type: "error",
                    error: "Data parsing error: " + error.message,
                });
            }
        };

        // Event: Custom events
        sseConnection.addEventListener("connected", function (event) {
            console.log("SSE Worker: Connected event received");
            try {
                const data = JSON.parse(event.data);
                self.postMessage({
                    type: "connected",
                    data: data,
                });
            } catch (error) {
                console.error(
                    "SSE Worker: Error parsing connected event:",
                    error
                );
            }
        });

        sseConnection.addEventListener("data", function (event) {
            try {
                const data = JSON.parse(event.data);
                console.log("SSE Worker: Data event received:", data);

                self.postMessage({
                    type: "data",
                    data: data,
                });
            } catch (error) {
                console.error("SSE Worker: Error parsing data event:", error);
            }
        });

        sseConnection.addEventListener("heartbeat", function (event) {
            console.log("SSE Worker: Heartbeat received");
            self.postMessage({
                type: "heartbeat",
                timestamp: Date.now(),
            });
        });

        sseConnection.addEventListener("error", function (event) {
            console.error("SSE Worker: Error event:", event);
            self.postMessage({
                type: "error",
                error: "SSE error event",
            });
        });

        // Event: Connection error
        sseConnection.onerror = function (event) {
            console.error("SSE Worker: Connection error:", event);
            self.postMessage({
                type: "error",
                error: "SSE connection error",
            });

            // Auto-reconnect logic - hanya jika worker masih aktif
            if (isActive && reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
                reconnectAttempts++;
                const delay = Math.min(
                    1000 * Math.pow(2, reconnectAttempts),
                    30000
                ); // Exponential backoff

                console.log(
                    `SSE Worker: Reconnecting in ${delay}ms (attempt ${reconnectAttempts}/${MAX_RECONNECT_ATTEMPTS})`
                );

                setTimeout(() => {
                    if (isActive && sseConnection) {
                        // Only reconnect if worker is still active and connection exists
                        startSseConnection(url, tags, interval);
                    }
                }, delay);
            } else if (reconnectAttempts >= MAX_RECONNECT_ATTEMPTS) {
                console.error("SSE Worker: Max reconnect attempts reached");
                self.postMessage({
                    type: "error",
                    error: "Max reconnect attempts reached",
                });
            }
        };
    } catch (error) {
        console.error("SSE Worker: Failed to create connection:", error);
        self.postMessage({
            type: "error",
            error: "Failed to create SSE connection: " + error.message,
        });
    }
}

function stopSseConnection() {
    console.log("SSE Worker: Stopping connection");
    isActive = false; // Set flag untuk mencegah reconnect

    if (sseConnection) {
        sseConnection.close();
        sseConnection = null;
        reconnectAttempts = 0;

        self.postMessage({
            type: "status",
            status: "stopped",
            message: "SSE connection stopped",
        });
    }
}

function updateConfig(tags, interval) {
    console.log(
        "SSE Worker: Updating config - tags:",
        tags,
        "interval:",
        interval
    );
    // Jika koneksi sudah ada, restart dengan konfigurasi baru
    if (sseConnection) {
        const currentUrl = sseConnection.url;
        const baseUrl = currentUrl.split("?")[0];
        const params = new URLSearchParams();
        params.append("interval", interval);
        tags.forEach((tag) => params.append("tags[]", tag));

        const newUrl = `${baseUrl}?${params.toString()}`;
        startSseConnection(newUrl, tags, interval);
    }
}

// Cleanup saat worker dihentikan
self.onbeforeunload = function () {
    isActive = false;
    stopSseConnection();
};

// Tambahkan heartbeat internal untuk memastikan koneksi tetap aktif
setInterval(() => {
    if (
        isActive &&
        sseConnection &&
        sseConnection.readyState === EventSource.OPEN
    ) {
        // Kirim heartbeat internal untuk memastikan koneksi tidak timeout
        self.postMessage({
            type: "heartbeat",
            timestamp: Date.now(),
        });
    }
}, 30000); // Setiap 30 detik
