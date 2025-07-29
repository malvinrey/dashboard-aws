@props(['percentage' => 0, 'value' => 'N/A'])

@php
    // Kalkulasi untuk SVG arc
    $radius = 40;
    $circumference = 2 * pi() * $radius;
    $offset = $circumference - ($percentage / 100) * $circumference;
@endphp

{{--
    Ini adalah Blade Component untuk SVG Humidity Gauge.
    Menerima 'percentage' (0-100) untuk bar dan 'value' untuk teks di tengah.
--}}
<svg class="humidity-gauge" viewBox="0 0 100 100">
    <!-- Lingkaran latar belakang (track) -->
    <circle class="gauge-track" cx="50" cy="50" r="{{ $radius }}" stroke-width="10" />
    <!-- Lingkaran progres (bar) -->
    <circle class="gauge-progress" cx="50" cy="50" r="{{ $radius }}" stroke-width="10"
        stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}" />
    <!-- Teks nilai di tengah -->
    <text x="50" y="55" class="gauge-text">
        {{ is_numeric($value) ? round($value) : 'N/A' }}<tspan font-size="0.5em">%</tspan>
    </text>
</svg>
