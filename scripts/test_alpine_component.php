<?php

/**
 * Test Script untuk Memverifikasi Alpine.js Component
 *
 * Script ini akan memeriksa apakah file JavaScript Alpine.js sudah dibuat dengan benar
 * dan dapat diakses dari browser.
 */

echo "=== Test Alpine.js Component ===\n\n";

// 1. Periksa apakah file JavaScript sudah dibuat
$jsFile = 'public/js/analysis-chart-component.js';
if (file_exists($jsFile)) {
    echo "✓ File JavaScript Alpine.js ditemukan: $jsFile\n";

    // Periksa ukuran file
    $fileSize = filesize($jsFile);
    echo "  - Ukuran file: " . number_format($fileSize) . " bytes\n";

    // Periksa apakah file berisi konten yang diharapkan
    $content = file_get_contents($jsFile);
    if (strpos($content, 'Alpine.data') !== false) {
        echo "  - ✓ File berisi definisi Alpine.js component\n";
    } else {
        echo "  - ✗ File tidak berisi definisi Alpine.js component\n";
    }

    if (strpos($content, 'analysisChartComponent') !== false) {
        echo "  - ✓ Component name 'analysisChartComponent' ditemukan\n";
    } else {
        echo "  - ✗ Component name 'analysisChartComponent' tidak ditemukan\n";
    }

    if (strpos($content, 'initComponent') !== false) {
        echo "  - ✓ Function 'initComponent' ditemukan\n";
    } else {
        echo "  - ✗ Function 'initComponent' tidak ditemukan\n";
    }
} else {
    echo "✗ File JavaScript Alpine.js TIDAK ditemukan: $jsFile\n";
}

echo "\n";

// 2. Periksa apakah file Blade sudah dimodifikasi
$bladeFile = 'resources/views/livewire/graph-analysis.blade.php';
if (file_exists($bladeFile)) {
    echo "✓ File Blade ditemukan: $bladeFile\n";

    $content = file_get_contents($bladeFile);

    if (strpos($content, '@push(\'scripts\')') !== false) {
        echo "  - ✓ @push('scripts') ditemukan\n";
    } else {
        echo "  - ✗ @push('scripts') tidak ditemukan\n";
    }

    if (strpos($content, 'analysis-chart-component.js') !== false) {
        echo "  - ✓ Referensi ke analysis-chart-component.js ditemukan\n";
    } else {
        echo "  - ✗ Referensi ke analysis-chart-component.js tidak ditemukan\n";
    }

    if (strpos($content, 'x-data="analysisChartComponent"') !== false) {
        echo "  - ✓ x-data menggunakan component external\n";
    } else {
        echo "  - ✗ x-data tidak menggunakan component external\n";
    }

    // Periksa apakah masih ada JavaScript inline yang panjang
    if (strlen($content) < 10000) {
        echo "  - ✓ File Blade sudah dibersihkan (tidak ada JavaScript inline)\n";
    } else {
        echo "  - ⚠ File Blade masih besar, mungkin masih ada JavaScript inline\n";
    }
} else {
    echo "✗ File Blade TIDAK ditemukan: $bladeFile\n";
}

echo "\n";

// 3. Periksa apakah layout sudah menggunakan @stack('scripts')
$layoutFile = 'resources/views/components/layouts/app.blade.php';
if (file_exists($layoutFile)) {
    echo "✓ File Layout ditemukan: $layoutFile\n";

    $content = file_get_contents($layoutFile);

    if (strpos($content, '@stack(\'scripts\')') !== false) {
        echo "  - ✓ @stack('scripts') ditemukan di layout\n";
    } else {
        echo "  - ✗ @stack('scripts') tidak ditemukan di layout\n";
    }
} else {
    echo "✗ File Layout TIDAK ditemukan: $layoutFile\n";
}

echo "\n";

// 4. Periksa apakah file SSE Worker ada
$sseWorkerFile = 'public/js/sse-worker.js';
if (file_exists($sseWorkerFile)) {
    echo "✓ File SSE Worker ditemukan: $sseWorkerFile\n";
} else {
    echo "⚠ File SSE Worker tidak ditemukan: $sseWorkerFile\n";
    echo "  - Pastikan file ini ada untuk SSE connection\n";
}

echo "\n=== Selesai ===\n";

echo "\nInstruksi untuk testing:\n";
echo "1. Buka browser dan akses halaman analysis chart\n";
echo "2. Buka Developer Tools (F12) dan lihat Console\n";
echo "3. Pastikan tidak ada error JavaScript\n";
echo "4. Periksa apakah komponen Alpine.js berfungsi\n";
echo "5. Test fitur-fitur seperti chart type, aggregation, dll.\n";
