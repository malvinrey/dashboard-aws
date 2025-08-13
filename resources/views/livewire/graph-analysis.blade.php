@php
    $endDate = now()->toDateTimeString();
    $startDate = now()->subDay()->toDateTimeString();
@endphp

<div>
    {{-- Sertakan file JavaScript Web Worker DAN file komponen Alpine --}}
    @push('scripts')
        <script src="{{ asset('js/sse-worker.js') }}" defer></script>
        <script src="{{ asset('js/analysis-chart-component.js') }}" defer></script>
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
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="channelSelect" value="ch1" checked
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">CH1</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="channelSelect" value="ch2" checked
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">CH2</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="channelSelect" value="ch3" checked
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">CH3</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="channelSelect" value="ch4"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">CH4</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="channelSelect" value="ch5"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">CH5</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="channelSelect" value="ch6"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">CH6</span>
                    </label>
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
