@props(['percentage' => 0])

@php
    // Definisikan dimensi yang SAMA PERSIS dengan wadah luar SVG
    $maxHeight = 110; // Tinggi total area cairan, sesuai dengan <rect> wadah
    $startY = 5; // Posisi Y (dari atas) tempat wadah dimulai

    // Hitung tinggi cairan berdasarkan persentase fill
    $fillHeight = $maxHeight * ($percentage / 100);

    // Hitung posisi Y baru untuk cairan agar dimulai dari bawah
    $fillY = $startY + $maxHeight - $fillHeight;
@endphp

<svg class="rainfall-gauge" viewBox="0 0 60 120">

    <!-- PERUBAHAN: Garis takar sekarang dibuat secara dinamis dengan loop -->
    @for ($i = 0; $i <= 100; $i += 10)
        @php
            // Hitung posisi Y untuk setiap garis takar
            $yPos = 115 - ($i / 100) * 110;

            // Tentukan panjang dan ketebalan garis
            $strokeWidth = $i % 50 == 0 ? 2 : ($i % 20 == 0 ? 1.5 : 1);
            $x2 = $i % 50 == 0 ? 12 : ($i % 20 == 0 ? 10 : 8);
        @endphp
        <line x1="5" y1="{{ $yPos }}" x2="{{ $x2 }}" y2="{{ $yPos }}" stroke="#adb5bd"
            stroke-width="{{ $strokeWidth }}" />
    @endfor

    <!-- Wadah luar transparan -->
    <rect x="15" y="5" width="30" height="110" rx="5" stroke="#adb5bd" stroke-width="2"
        fill="rgba(233, 236, 239, 0.5)" />

    <!-- Isian air (biru) dengan koordinat yang sudah benar -->
    <rect x="15" y="{{ $fillY }}" width="30" height="{{ $fillHeight }}" rx="5" fill="#007bff"
        style="transition: all 0.5s ease;" />
</svg>
