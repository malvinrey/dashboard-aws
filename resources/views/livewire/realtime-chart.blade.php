<div>
    {{-- CSS yang spesifik untuk komponen ini bisa diletakkan di sini --}}
    <style>
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 24px;
        }

        .chart-card {
            background-color: var(--bg-white, #ffffff);
            padding: 16px;
            border-radius: var(--radius-lg, 0.5rem);
            border: 1px solid var(--border-color, #e5e7eb);
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
        }

        .chart-card-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 12px 0;
            color: var(--text-primary, #111827);
        }

        .chart-container-wrapper {
            position: relative;
            height: 280px;
            flex-grow: 1;
        }

        .loading-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 1000;
        }
    </style>

    {{-- Elemen ini akan terus melakukan polling untuk data real-time terbaru --}}
    <div wire:poll.1s="getLatestDataPoint">
        {{-- Loading indicator untuk real-time updates --}}
        <div class="loading-indicator" wire:loading wire:target="getLatestDataPoint">
            Updating real-time data...
        </div>

        <div class="charts-grid" wire:ignore>
            @foreach ($allTags as $metric)
                <div class="chart-card">
                    <h3 class="chart-card-title">{{ ucfirst($metric) }}</h3>
                    <div class="chart-container-wrapper">
                        <canvas id="chart-{{ $metric }}"></canvas>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Skrip yang terikat dengan komponen ini --}}
    @script
        <script>
            document.addEventListener('livewire:navigated', () => {
                console.log('RealtimeChart: Initializing...');
                let chartInstances = {};

                const createStreamingChart = (ctx, metricName) => {
                    console.log('Creating chart for:', metricName);
                    if (chartInstances[metricName]) {
                        chartInstances[metricName].destroy();
                    }

                    const colors = [
                        '#3b82f6', '#ef4444', '#10b981', '#f59e0b',
                        '#8b5cf6', '#06b6d4', '#84cc16', '#f97316'
                    ];
                    const colorIndex = Object.keys(chartInstances).length % colors.length;

                    // Check if streaming plugin is available
                    const isStreamingAvailable = typeof Chart !== 'undefined' &&
                        Chart.registry &&
                        Chart.registry.controllers.streaming;

                    console.log('Streaming plugin available:', isStreamingAvailable);

                    const chartConfig = {
                        type: 'line',
                        data: {
                            datasets: [{
                                label: metricName,
                                data: [],
                                borderColor: colors[colorIndex],
                                backgroundColor: colors[colorIndex] + '20',
                                borderWidth: 2,
                                fill: false,
                                tension: 0.2,
                                pointRadius: 3,
                                pointHoverRadius: 5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false
                                }
                            },
                            scales: {
                                x: {
                                    type: 'time',
                                    time: {
                                        unit: 'second',
                                        displayFormats: {
                                            second: 'HH:mm:ss',
                                            minute: 'HH:mm',
                                            hour: 'MMM dd HH:mm'
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Time'
                                    }
                                },
                                y: {
                                    title: {
                                        display: true,
                                        text: 'Value'
                                    },
                                    beginAtZero: false
                                }
                            },
                            interaction: {
                                mode: 'nearest',
                                axis: 'x',
                                intersect: false
                            }
                        }
                    };

                    // Add streaming configuration if plugin is available
                    if (isStreamingAvailable) {
                        chartConfig.options.scales.x.type = 'realtime';
                        chartConfig.options.scales.x.realtime = {
                            duration: 30000, // 30 seconds
                            ttl: 60000, // 1 minute
                            delay: 1000, // 1 second delay
                            pause: false,
                            onRefresh: function(chart) {
                                console.log('Chart refreshed:', chart.data.datasets[0].label);
                            }
                        };
                    }

                    chartInstances[metricName] = new Chart(ctx, chartConfig);
                    console.log('Chart created for:', metricName, 'Streaming:', isStreamingAvailable);
                };

                // Initialize all charts
                document.querySelectorAll('.charts-grid canvas').forEach(canvas => {
                    // Extract metric name from canvas ID - remove 'chart-' prefix
                    const metricName = canvas.id.replace('chart-', '');
                    const ctx = canvas.getContext('2d');
                    if (ctx) {
                        createStreamingChart(ctx, metricName);
                        console.log('Created chart for canvas:', canvas.id, 'with metric name:', metricName);
                    } else {
                        console.error('Could not get context for canvas:', canvas.id);
                    }
                });

                console.log('Total charts created:', Object.keys(chartInstances));
                console.log('Available charts:', Object.keys(chartInstances));

                // Event listener for new data points
                document.addEventListener('new-streaming-point', event => {
                    console.log('Received new streaming point:', event.detail);
                    const newData = event.detail.data;
                    if (!newData || !newData.metrics) {
                        console.warn('Invalid data received:', newData);
                        return;
                    }

                    console.log('Available metrics in data:', Object.keys(newData.metrics));
                    console.log('Available charts:', Object.keys(chartInstances));

                    for (const metricName in newData.metrics) {
                        // Try multiple ways to match chart names
                        let chart = null;
                        let matchedName = null;

                        // Method 1: Direct match
                        if (chartInstances[metricName]) {
                            chart = chartInstances[metricName];
                            matchedName = metricName;
                        }
                        // Method 2: Replace underscore with space
                        else if (chartInstances[metricName.replace(/_/g, ' ')]) {
                            chart = chartInstances[metricName.replace(/_/g, ' ')];
                            matchedName = metricName.replace(/_/g, ' ');
                        }
                        // Method 3: Replace space with underscore
                        else if (chartInstances[metricName.replace(/ /g, '_')]) {
                            chart = chartInstances[metricName.replace(/ /g, '_')];
                            matchedName = metricName.replace(/ /g, '_');
                        }
                        // Method 4: Case insensitive match
                        else {
                            const chartNames = Object.keys(chartInstances);
                            const foundChart = chartNames.find(name =>
                                name.toLowerCase() === metricName.toLowerCase() ||
                                name.toLowerCase() === metricName.replace(/_/g, ' ').toLowerCase()
                            );
                            if (foundChart) {
                                chart = chartInstances[foundChart];
                                matchedName = foundChart;
                            }
                        }

                        if (!chart) {
                            console.warn('Chart not found for metric:', metricName);
                            console.log('Tried to match:', metricName);
                            console.log('Available charts:', Object.keys(chartInstances));
                            continue;
                        }

                        console.log('Successfully matched metric:', metricName, 'to chart:', matchedName);

                        const value = parseFloat(newData.metrics[metricName]);
                        if (isNaN(value)) {
                            console.warn('Invalid value for metric:', metricName, newData.metrics[metricName]);
                            continue;
                        }

                        const timestamp = new Date(newData.timestamp).getTime();
                        console.log('Adding point to chart:', matchedName, 'Value:', value, 'Time:', new Date(
                            timestamp));

                        // Add new data point
                        chart.data.datasets[0].data.push({
                            x: timestamp,
                            y: value
                        });

                        // Keep only last 100 points to prevent memory issues
                        if (chart.data.datasets[0].data.length > 100) {
                            chart.data.datasets[0].data.shift();
                        }

                        // Update chart - use 'none' for better performance
                        chart.update('none');
                    }
                });

                // Debug: Log when component is ready
                console.log('RealtimeChart: Initialization complete');
                console.log('Available charts:', Object.keys(chartInstances));

                // Test data point after 2 seconds to verify everything works
                setTimeout(() => {
                    console.log('Testing chart functionality...');
                    const testCharts = Object.keys(chartInstances);
                    if (testCharts.length > 0) {
                        const testChart = chartInstances[testCharts[0]];
                        if (testChart && testChart.data.datasets[0].data.length === 0) {
                            console.log('Adding test data point to verify chart functionality');
                            testChart.data.datasets[0].data.push({
                                x: Date.now(),
                                y: Math.random() * 100
                            });
                            testChart.update('none');
                        }
                    }
                }, 2000);
            });
        </script>
    @endscript
</div>
