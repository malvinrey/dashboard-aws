// resources/js/app.js

// 1. Impor bootstrap (untuk Axios, Laravel Echo, dll.)
import "./bootstrap";

// 2. Impor Alpine.js
import Alpine from "alpinejs";
window.Alpine = Alpine;

// 3. Impor SEMUA file JavaScript kustom Anda di sini
import ScadaWebSocketClient from "./scada-websocket-client.js";
import "./analysis-chart-component.js";
import ScadaChartManager from "./scada-chart-manager.js";

// 4. Buat global variables untuk kompatibilitas
window.ScadaWebSocketClient = ScadaWebSocketClient;
window.ScadaChartManager = ScadaChartManager;

// 5. Inisialisasi Alpine.js
Alpine.start();
