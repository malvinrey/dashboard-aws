@php
    $endDate = now()->toDateTimeString();
    $startDate = now()->subDay()->toDateTimeString();
@endphp

<div>
    {{-- Sertakan file JavaScript WebSocket dan komponen Alpine --}}
    @push('scripts')
        <!-- Include Pusher library for WebSocket client -->
        <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
        <script src="{{ asset('js/scada-websocket-client.js') }}" defer></script>
        <script src="{{ asset('js/scada-chart-manager.js') }}" defer></script>
        <script src="{{ asset('js/analysis-chart-component.js') }}" defer></script>
        <script>
            // Expose default tags from backend for initial WebSocket connection and UI rendering
            window.ANALYSIS_DEFAULT_TAGS = @json($selectedTags);
            window.ANALYSIS_ALL_TAGS = @json($allTags);
        </script>
    @endpush

    {{-- Panggil komponen Alpine dengan cara yang sangat bersih --}}
    <div wire:init="loadHistoricalData('{{ $startDate }}', '{{ $endDate }}')" x-data="analysisChartComponent"
        x-init="initComponent()" x-on:beforeunload="cleanup()" id="analysisChartContainer">
        <!-- Loading State -->
        <div wire:loading wire:target="loadHistoricalData"
            class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10">
            <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
                <p class="text-lg font-semibold text-gray-700">Loading historical data, please wait...</p>
            </div>
        </div>

        <!-- Main Container -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">SCADA Data Analysis</h2>
                    <p class="text-gray-600 mt-1">Real-time monitoring and historical data visualization</p>
                </div>

                <!-- Connection Status -->
                <div class="flex items-center space-x-4 mt-4 lg:mt-0">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 rounded-full bg-gray-400" id="connectionIndicator"></div>
                        <span id="connectionStatus" class="text-sm font-medium">Connecting...</span>
                    </div>

                    <div class="text-sm text-gray-500">
                        Last update: <span id="lastUpdateTime">-</span>
                    </div>
                </div>
            </div>

            <!-- Control Panel -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                <!-- Chart Type Selector -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chart Type</label>
                    <select name="chartType"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="line">Line Chart</option>
                        <option value="scatter">Scatter Plot</option>
                        <option value="bar">Bar Chart</option>
                    </select>
                </div>

                <!-- Aggregation Interval -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Aggregation</label>
                    <select name="aggregationInterval"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="1">1 Second</option>
                        <option value="5" selected>5 Seconds</option>
                        <option value="10">10 Seconds</option>
                        <option value="30">30 Seconds</option>
                        <option value="60">1 Minute</option>
                        <option value="300">5 Minutes</option>
                        <option value="900">15 Minutes</option>
                        <option value="1800">30 Minutes</option>
                        <option value="3600">1 Hour</option>
                    </select>
                </div>

                <!-- Time Range Picker -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="datetime-local" name="startDate" value="{{ $startDate }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input type="datetime-local" name="endDate" value="{{ $endDate }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center space-x-4 mb-6">
                <button id="playPauseBtn"
                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md flex items-center space-x-2 transition-colors">
                    <i class="fas fa-play"></i>
                    <span>Play</span>
                </button>

                <button id="exportBtn"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md flex items-center space-x-2 transition-colors">
                    <i class="fas fa-download"></i>
                    <span>Export CSV</span>
                </button>

                <button onclick="location.reload()"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md flex items-center space-x-2 transition-colors">
                    <i class="fas fa-redo"></i>
                    <span>Refresh</span>
                </button>
            </div>

            <!-- Channel Selection -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Channel Selection</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    @foreach ($allTags as $tag)
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="channelSelect" value="{{ $tag }}"
                                @checked(in_array($tag, $selectedTags))
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">{{ Str::headline($tag) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Chart Container -->
            <div class="bg-gray-50 rounded-lg p-4">
                <div id="analysisChart" style="width:100%; height:500px;"></div>
            </div>

            <!-- Data Statistics -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-blue-800">Total Data Points</h4>
                    <p class="text-2xl font-bold text-blue-900" id="totalDataPoints">0</p>
                </div>

                <div class="bg-green-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-green-800">Active Channels</h4>
                    <p class="text-2xl font-bold text-green-900" id="activeChannels">0</p>
                </div>

                <div class="bg-purple-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-purple-800">Update Rate</h4>
                    <p class="text-2xl font-bold text-purple-900" id="updateRate">0/s</p>
                </div>
            </div>
        </div>

        <!-- WebSocket Integration Script -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize Laravel Echo for compatibility
                if (typeof ScadaEchoClient !== 'undefined') {
                    new ScadaEchoClient({
                        serverUrl: 'http://127.0.0.1:6001',
                        appKey: '{{ env('PUSHER_APP_KEY', 'scada_dashboard_key_2024') }}',
                        cluster: '{{ env('PUSHER_APP_CLUSTER', 'mt1') }}'
                    });
                    console.log('Laravel Echo initialized for compatibility.');
                }

                // Initialize WebSocket client for real-time data
                const wsClient = new ScadaWebSocketClient({
                    serverUrl: 'ws://127.0.0.1:6001',
                    appKey: '{{ env('PUSHER_APP_KEY', 'scada_dashboard_key_2024') }}',
                    appId: '{{ env('PUSHER_APP_ID', '12345') }}',
                    cluster: '{{ env('PUSHER_APP_CLUSTER', 'mt1') }}',
                    encrypted: false,
                    channel: 'scada-data'
                });

                // Initialize chart manager with throttling
                const chartElement = document.getElementById('analysisChart');
                const chartManager = new ScadaChartManager(chartElement, {
                    maxDataPoints: 1000,
                    updateInterval: 100,
                    aggregationEnabled: true
                });

                // WebSocket event handlers
                wsClient.onConnect = function() {
                    console.log('WebSocket connected successfully');
                    updateConnectionStatus('connected', 'Connected');
                };

                wsClient.onDisconnect = function() {
                    console.log('WebSocket disconnected');
                    updateConnectionStatus('disconnected', 'Disconnected');
                };

                wsClient.onMessage = function(data) {
                    if (data.event === 'scada.data.received') {
                        const scadaData = data.data;
                        chartManager.addData(scadaData);

                        // Update Livewire component
                        if (window.Livewire) {
                            window.Livewire.dispatch('chart-data-updated', scadaData);
                        }

                        updateLastUpdateTime();
                    }
                };

                wsClient.onError = function(error) {
                    console.error('WebSocket error:', error);
                    updateConnectionStatus('error', 'Error');
                };

                // Update connection status UI
                function updateConnectionStatus(status, text) {
                    const indicator = document.getElementById('connectionIndicator');
                    const statusText = document.getElementById('connectionStatus');

                    if (indicator && statusText) {
                        indicator.className = `w-3 h-3 rounded-full ${
                            status === 'connected' ? 'bg-green-500' :
                            status === 'disconnected' ? 'bg-red-500' : 'bg-yellow-500'
                        }`;
                        statusText.textContent = text;
                    }
                }

                // Update last update time
                function updateLastUpdateTime() {
                    const lastUpdateElement = document.getElementById('lastUpdateTime');
                    if (lastUpdateElement) {
                        lastUpdateElement.textContent = new Date().toLocaleTimeString();
                    }
                }

                // Cleanup on page unload
                window.addEventListener('beforeunload', function() {
                    if (wsClient) {
                        wsClient.disconnect();
                    }
                    if (chartManager) {
                        chartManager.destroy();
                    }
                });
            });
        </script>

        <!-- Custom CSS -->
        <style>
            /* Chart container styling */
            #analysisChart {
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }

            /* Custom scrollbar */
            ::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }

            ::-webkit-scrollbar-track {
                background: #f1f5f9;
                border-radius: 4px;
            }

            ::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 4px;
            }

            ::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }

            /* Animation classes */
            .animate-pulse {
                animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            }

            @keyframes pulse {

                0%,
                100% {
                    opacity: 1;
                }

                50% {
                    opacity: .5;
                }
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .grid-cols-1.md\:grid-cols-3 {
                    grid-template-columns: repeat(1, minmax(0, 1fr));
                }

                #analysisChart {
                    height: 400px !important;
                }
            }

            /* Chart tooltip customization */
            .js-plotly-plot .plotly .main-svg {
                border-radius: 8px;
            }

            /* Loading animation */
            .loading-spinner {
                border: 3px solid #f3f3f3;
                border-top: 3px solid #3498db;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                0% {
                    transform: rotate(0deg);
                }

                100% {
                    transform: rotate(360deg);
                }
            }
        </style>
    </div>
</div>
