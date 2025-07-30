<div wire:poll.5s="updateData">
    @if (!empty($metrics))
        <div class="metric-grid-container">

            {{-- Kartu Kompas & Angin --}}
            @if (isset($metrics['wind_direction']))
                <div class="metric-card compass-card">
                    <div class="card-header">
                        <div class="card-title">Wind</div>
                    </div>
                    @php
                        // Ambil data dari array $metrics yang sudah diproses
                        $windDirection = (float) ($metrics['wind_direction']['value'] ?? 0);
                        if (!is_numeric($windDirection)) {
                            $windDirection = 0;
                        }
                    @endphp
                    {{-- Mereferensikan komponen x-compass --}}
                    <x-compass :direction="$windDirection" />
                    <div class="wind-details">
                        <div class="wind-speed">
                            <span class="wind-speed-value">{{ $metrics['wind_speed']['value'] ?? 'N/A' }}</span>
                            <span class="card-unit">{{ $metrics['wind_speed']['unit'] ?? 'm/s' }}</span>
                        </div>
                        <div class="wind-direction-text">
                            <span class="wind-direction-value">{{ $metrics['wind_direction']['value'] ?? 'N/A' }}</span>
                            <span class="card-unit">{{ $metrics['wind_direction']['unit'] ?? '°' }}</span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Kartu Termometer --}}
            @if (isset($metrics['temperature']))
                <div class="metric-card thermometer-card">
                    <div class="card-header">
                        <div class="card-title">Temperature</div>
                    </div>
                    @php
                        $tempMetric = $metrics['temperature'];
                        $fillPercentage = min(100, max(0, (float) ($tempMetric['value'] ?? 0))); // Asumsi skala 0-100
                    @endphp
                    <div class="card-details">
                        <div class="thermometer-content-wrapper">
                            <div class="thermometer-text-group">
                                <div class="card-value">
                                    {{ $tempMetric['value'] }} <span class="card-unit">{{ $tempMetric['unit'] }}</span>
                                </div>
                                <div class="card-change">
                                    @if (isset($tempMetric['change']))
                                        <span class="change-indicator {{ $tempMetric['change_class'] }}">
                                            {{ $tempMetric['change'] >= 0 ? '↑' : '↓' }}
                                            {{ abs($tempMetric['change']) }}%
                                        </span>
                                    @endif
                                    <span
                                        class="card-timestamp">{{ \Carbon\Carbon::parse($tempMetric['timestamp'])->format('H:i:s') }}</span>
                                </div>
                            </div>
                            {{-- Mereferensikan komponen x-thermometer --}}
                            <x-thermometer :fill="$fillPercentage" />
                        </div>
                    </div>
                </div>
            @endif

            {{-- Kartu Humidity --}}
            @if (isset($metrics['humidity']))
                <div class="metric-card humidity-card">
                    <div class="card-header">
                        <div class="card-title">Humidity</div>
                    </div>
                    @php $humidityMetric = $metrics['humidity']; @endphp
                    <div class="card-details">
                        {{-- Mereferensikan komponen x-humidity-gauge --}}
                        <x-humidity-gauge :percentage="(float) $humidityMetric['value']" :value="$humidityMetric['value']" />
                        <div class="card-change">
                            @if (isset($humidityMetric['change']))
                                <span class="change-indicator {{ $humidityMetric['change_class'] }}">
                                    {{ $humidityMetric['change'] >= 0 ? '↑' : '↓' }}
                                    {{ abs($humidityMetric['change']) }}%
                                </span>
                            @endif
                            <span
                                class="card-timestamp">{{ \Carbon\Carbon::parse($humidityMetric['timestamp'])->format('H:i:s') }}</span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Kartu Rainfall --}}
            @if (isset($metrics['rainfall']))
                <div class="metric-card rainfall-card">
                    <div class="card-header">
                        <div class="card-title">Rainfall</div>
                    </div>
                    @php
                        $rainfallMetric = $metrics['rainfall'];
                        $fillPercentage = min(100, ((float) $rainfallMetric['value'] / 50) * 100); // Asumsi max 50mm
                    @endphp
                    <div class="card-details">
                        <div class="rainfall-content-wrapper">
                            <div class="rainfall-text-group">
                                <div class="card-value">
                                    {{ $rainfallMetric['value'] }} <span
                                        class="card-unit">{{ $rainfallMetric['unit'] }}</span>
                                </div>
                                <div class="card-change">
                                    @if (isset($rainfallMetric['change']))
                                        <span class="change-indicator {{ $rainfallMetric['change_class'] }}">
                                            {{ $rainfallMetric['change'] >= 0 ? '↑' : '↓' }}
                                            {{ abs($rainfallMetric['change']) }}%
                                        </span>
                                    @endif
                                    <span
                                        class="card-timestamp">{{ \Carbon\Carbon::parse($rainfallMetric['timestamp'])->format('H:i:s') }}</span>
                                </div>
                            </div>
                            {{-- Mereferensikan komponen x-rainfall-gauge --}}
                            <x-rainfall-gauge :percentage="$fillPercentage" />
                        </div>
                    </div>
                </div>
            @endif

            {{-- Kartu Pressure --}}
            @if (isset($metrics['pressure']))
                <div class="metric-card pressure-card">
                    <div class="card-header">
                        <div class="card-title">Pressure</div>
                    </div>
                    @php
                        $pressureMetric = $metrics['pressure'];
                        $fillPercentage = (((float) $pressureMetric['value'] - 950) / (1050 - 950)) * 100; // Asumsi skala 950-1050 hPa
                    @endphp
                    <div class="card-details">
                        {{-- Mereferensikan komponen x-pressure-gauge --}}
                        <x-pressure-gauge :percentage="$fillPercentage" />
                        <div class="pressure-text-group">
                            <div class="card-value">
                                {{ $pressureMetric['value'] }} <span
                                    class="card-unit">{{ $pressureMetric['unit'] }}</span>
                            </div>
                            <div class="card-change">
                                @if (isset($pressureMetric['change']))
                                    <span class="change-indicator {{ $pressureMetric['change_class'] }}">
                                        {{ $pressureMetric['change'] >= 0 ? '↑' : '↓' }}
                                        {{ abs($pressureMetric['change']) }}%
                                    </span>
                                @endif
                                <span
                                    class="card-timestamp">{{ \Carbon\Carbon::parse($pressureMetric['timestamp'])->format('H:i:s') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Looping untuk kartu metrik generik lainnya --}}
            @php
                $special_tags = ['wind_speed', 'wind_direction', 'temperature', 'humidity', 'rainfall', 'pressure'];
            @endphp
            @foreach ($metrics as $tag => $metric)
                @if (!in_array($tag, $special_tags))
                    <div class="metric-card" wire:key="{{ $tag }}">
                        <div class="card-header">
                            <div class="card-title">{{ ucfirst(str_replace('_', ' ', $tag)) }}</div>
                        </div>
                        <div class="card-details">
                            <div class="card-value">
                                {{ $metric['value'] }} <span class="card-unit">{{ $metric['unit'] }}</span>
                            </div>
                            <div class="card-change">
                                @if (isset($metric['change']))
                                    <span class="change-indicator {{ $metric['change_class'] }}">
                                        {{ $metric['change'] >= 0 ? '↑' : '↓' }}
                                        {{ abs($metric['change']) }}%
                                    </span>
                                @endif
                                <span class="card-timestamp">
                                    {{ \Carbon\Carbon::parse($metric['timestamp'])->format('H:i:s') }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach

            {{-- Kartu Informasi Payload Terakhir --}}
            <div class="metric-card info-card">
                <div class="card-header">
                    <div class="card-title">Last Payload Info</div>
                </div>
                <div class="card-details">
                    @if ($lastPayloadInfo)
                        <div class="info-row">
                            <span class="info-label">Group:</span>
                            <span class="info-value">{{ $lastPayloadInfo->nama_group }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Timestamp:</span>
                            <span
                                class="info-value">{{ \Carbon\Carbon::parse($lastPayloadInfo->timestamp_device)->format('d M Y, H:i:s') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Batch ID:</span>
                            <span class="info-value batch-id">{{ $lastPayloadInfo->batch_id }}</span>
                        </div>
                    @else
                        <p class="no-payload-text">No payload received yet.</p>
                    @endif
                </div>
            </div>

        </div>
    @else
        <div style="text-align: center; color: rgba(0,0,0,0.60); padding: 40px;">
            <p>Waiting for incoming data...</p>
        </div>
    @endif
</div>
