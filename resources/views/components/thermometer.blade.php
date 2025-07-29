@props(['fill' => 0])

{{--
    Ini adalah Blade Component untuk SVG Termometer.
    Ia menerima satu properti: 'fill', yaitu persentase ketinggian cairan (0-100).
--}}
@php
    // Definisikan dimensi tabung termometer di dalam SVG
    $tubeHeight = 85; // Tinggi total area cairan di dalam tabung
    $tubeY_start = 10; // Posisi Y (dari atas) di mana tabung dimulai

    // Hitung tinggi cairan berdasarkan persentase fill
    $fillHeight = $tubeHeight * ($fill / 100);

    // Hitung posisi Y baru untuk cairan.
    // Ini adalah kunci perbaikannya: posisi Y dimulai dari dasar tabung dikurangi tinggi cairan.
    $fillY = $tubeY_start + $tubeHeight - $fillHeight;
@endphp

<svg class="thermometer" viewBox="0 0 40 120">
    <!-- Tabung luar -->
    <rect x="15" y="5" width="10" height="90" rx="5" fill="#e9ecef" />
    <!-- Bohlam bawah -->
    <circle cx="20" cy="100" r="15" fill="#e9ecef" />
    <!-- Cairan di bohlam -->
    <circle cx="20" cy="100" r="12" fill="#dc3545" />

    {{-- PERUBAHAN: Latar belakang putih di dalam tabung dihapus untuk memperbaiki bug visual --}}

    <!-- Cairan di tabung sekarang digambar dengan y dan height yang dinamis -->
    <rect class="thermometer-fill" x="18" y="{{ $fillY }}" width="4" height="{{ $fillHeight }}"
        fill="#dc3545" style="transition: all 0.5s ease;" />
</svg>
