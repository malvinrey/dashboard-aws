@props(['percentage' => 0])

@php
    // Kalkulasi untuk rotasi jarum.
    // Gauge ini memiliki rentang 270 derajat (dari -135 hingga +135 derajat dari posisi 6).
    $rotation = ($percentage / 100) * 270 - 135;
@endphp

<svg class="pressure-gauge" viewBox="0 0 100 100">
    <!-- Lingkaran latar belakang utama -->
    <circle cx="50" cy="50" r="48" fill="#f8f9fa" stroke="#dee2e6" stroke-width="2" />

    <!-- Garis takaran dibuat dengan loop -->
    @for ($i = 0; $i <= 10; $i++)
        @php
            $angle = $i * 27 - 135; // 10 takaran dalam 270 derajat
            $isMajorTick = $i % 2 == 0;
        @endphp
        <line x1="50" y1="10" x2="50" y2="{{ $isMajorTick ? 20 : 15 }}" stroke="#6c757d"
            stroke-width="{{ $isMajorTick ? 2 : 1 }}" transform="rotate({{ $angle }}, 50, 50)" />
    @endfor

    <!-- Jarum penunjuk -->
    <g style="transform-origin: 50px 50px; transition: transform 0.5s ease; transform: rotate({{ $rotation }}deg);">
        <line x1="50" y1="50" x2="50" y2="15" class="gauge-needle" />
        <circle cx="50" cy="50" r="4" class="gauge-needle-center" />
    </g>
</svg>
