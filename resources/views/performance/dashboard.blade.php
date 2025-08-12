@extends('components.layouts.app')

@section('title', 'Performance Dashboard')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Performance Dashboard</h1>
            <p class="text-gray-600 dark:text-gray-400">Monitor database health and system performance in real-time</p>
        </div>

        <!-- Database Health Status -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div
                        class="p-3 rounded-full {{ $healthData['status'] === 'healthy' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if ($healthData['status'] === 'healthy')
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            @endif
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white capitalize">
                            {{ $healthData['status'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Table Size</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $healthData['table_size_mb'] }} MB
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Records</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ number_format($healthData['total_records']) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Data Age</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            @if ($healthData['latest_data_age_minutes'])
                                {{ $healthData['latest_data_age_minutes'] }} min
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Insertion Rate (Last Hour)</h3>
                <div class="flex items-center justify-between">
                    <span
                        class="text-3xl font-bold text-blue-600">{{ number_format($healthData['insertion_rate_last_hour']) }}</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">records/hour</span>
                </div>
                <div class="mt-4">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        @php
                            $ratePercentage = min(($healthData['insertion_rate_last_hour'] / 10000) * 100, 100);
                        @endphp
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $ratePercentage }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        @if ($healthData['insertion_rate_last_hour'] > 5000)
                            High activity detected
                        @elseif($healthData['insertion_rate_last_hour'] > 1000)
                            Normal activity
                        @else
                            Low activity
                        @endif
                    </p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Health Check Performance</h3>
                <div class="flex items-center justify-between">
                    <span class="text-3xl font-bold text-green-600">{{ $healthData['health_check_time_ms'] }}</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">ms</span>
                </div>
                <div class="mt-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Last checked: {{ $healthData['last_check'] }}
                    </p>
                    <button id="refreshMetrics"
                        class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        Refresh Now
                    </button>
                </div>
            </div>
        </div>

        <!-- Recommendations -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Optimization Recommendations</h3>
            <div id="recommendations" class="space-y-3">
                <div class="animate-pulse">
                    <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                    <div class="h-4 bg-gray-200 rounded w-1/2 mt-2"></div>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">System Information</h3>
            <div id="systemInfo" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="animate-pulse">
                    <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                    <div class="h-4 bg-gray-200 rounded w-3/4 mt-2"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Load initial data
            loadRecommendations();
            loadSystemInfo();
            loadMetrics();

            // Set up auto-refresh every 30 seconds
            setInterval(loadMetrics, 30000);

            // Manual refresh button
            document.getElementById('refreshMetrics').addEventListener('click', function() {
                loadMetrics();
                loadRecommendations();
                loadSystemInfo();
            });
        });

        function loadRecommendations() {
            fetch('/performance/recommendations')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('recommendations');
                    container.innerHTML = '';

                    if (data.recommendations.immediate.length > 0) {
                        container.innerHTML += '<div class="text-red-600 font-medium">Immediate Actions:</div>';
                        data.recommendations.immediate.forEach(rec => {
                            container.innerHTML +=
                                `<div class="text-sm text-gray-700 dark:text-gray-300 ml-4">• ${rec}</div>`;
                        });
                    }

                    if (data.recommendations.short_term.length > 0) {
                        container.innerHTML +=
                            '<div class="text-yellow-600 font-medium mt-3">Short Term Optimizations:</div>';
                        data.recommendations.short_term.forEach(rec => {
                            container.innerHTML +=
                                `<div class="text-sm text-gray-700 dark:text-gray-300 ml-4">• ${rec}</div>`;
                        });
                    }

                    if (data.recommendations.long_term.length > 0) {
                        container.innerHTML +=
                            '<div class="text-blue-600 font-medium mt-3">Long Term Optimizations:</div>';
                        data.recommendations.long_term.forEach(rec => {
                            container.innerHTML +=
                                `<div class="text-sm text-gray-700 dark:text-gray-300 ml-4">• ${rec}</div>`;
                        });
                    }

                    if (Object.values(data.recommendations).every(arr => arr.length === 0)) {
                        container.innerHTML = '<div class="text-green-600">All systems operating normally</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading recommendations:', error);
                    document.getElementById('recommendations').innerHTML =
                        '<div class="text-red-600">Failed to load recommendations</div>';
                });
        }

        function loadSystemInfo() {
            fetch('/performance/metrics')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('systemInfo');
                    container.innerHTML = `
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">PHP Version</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">${data.system_info.php_version}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Laravel Version</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">${data.system_info.laravel_version}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Memory Usage</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">${data.system_info.memory_usage}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Peak Memory</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">${data.system_info.peak_memory}</p>
                </div>
            `;
                })
                .catch(error => {
                    console.error('Error loading system info:', error);
                    document.getElementById('systemInfo').innerHTML =
                        '<div class="text-red-600">Failed to load system information</div>';
                });
        }

        function loadMetrics() {
            fetch('/performance/metrics')
                .then(response => response.json())
                .then(data => {
                    // Update metrics in real-time
                    console.log('Metrics updated:', data);
                })
                .catch(error => {
                    console.error('Error loading metrics:', error);
                });
        }
    </script>
@endsection
