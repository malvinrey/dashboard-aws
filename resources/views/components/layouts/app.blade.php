<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livewire Chart Analysis</title>
    {{-- Menggunakan Google Fonts untuk tipografi yang lebih baik --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    </script>
    <style>
        /* Semua gaya CSS dari versi sebelumnya tetap sama */
        :root {
            --border-color: #e5e7eb;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --bg-light: #f9fafb;
            --bg-white: #ffffff;
            --primary-color: #3b82f6;
            --primary-hover-color: #2563eb;
            --green-color: #10b981;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --radius-md: 0.375rem;
            --radius-lg: 0.5rem;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            background-color: var(--bg-light);
            color: var(--text-primary);
        }

        .dashboard-container {
            padding: 24px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .header-frame {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .title-status-container {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .main-title {
            color: var(--text-primary);
            font-weight: 700;
            font-size: 28px;
            margin: 0;
        }

        .status-indicator {
            display: flex;
            padding: 8px 16px;
            align-items: center;
            gap: 8px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            background-color: var(--bg-white);
            box-shadow: var(--shadow-sm);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--green-color);
        }

        .status-text {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 14px;
        }

        .tabs {
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 24px;
        }

        .tab-item {
            display: inline-flex;
            gap: 24px;
        }

        .tab-text a {
            padding: 12px 4px;
            display: inline-block;
            text-decoration: none;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 16px;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease-in-out;
        }

        .tab-text a.active,
        .tab-text a:hover {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
        }

        .chart-page-container {
            background-color: var(--bg-white);
            padding: 24px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
        }

        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
            align-items: flex-end;
            position: relative;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        /* Khusus untuk filter group yang berisi select metric */
        .filter-group:has(#metrics-select2) {
            min-width: 310px;
            max-width: 310px;
            flex-shrink: 0;
            /* Pastikan filter group tidak membatasi tinggi */
            /* height: auto; */
        }

        .filter-group label {
            font-weight: 500;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .filter-group input[type="date"],
        .filter-group select,
        .interval-buttons button,
        .btn-primary {
            height: 42px;
            padding: 0 14px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background-color: var(--bg-white);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .filter-group input[type="date"]:focus,
        .filter-group select:focus,
        .interval-buttons button:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }

        /* Styling untuk select metric agar konsisten dengan filter lainnya */
        #metrics-select2 {
            display: none;
            /* Sembunyikan select asli karena sudah diganti dengan Select2 */
        }


        cursor: pointer;
        transition: background-color 0.2s ease;
        }

        .form-select-option:hover {
            background-color: var(--bg-light);
        }

        .form-select-option.selected {
            background-color: var(--primary-color);
            color: white;
        }

        .interval-buttons {
            display: flex;
            gap: -1px;
        }

        .interval-buttons button {
            cursor: pointer;
            white-space: nowrap;
        }

        .interval-buttons button:not(:last-child) {
            border-right: none;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .interval-buttons button:not(:first-child) {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .interval-buttons button.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            z-index: 1;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover-color);
        }

        .btn-secondary {
            background-color: var(--bg-white);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-sm);
            padding: 8px 16px;
            border-radius: var(--radius-md);
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background-color: var(--bg-light);
            border-color: var(--primary-color);
        }

        .metrics-actions {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        /* Gaya khusus untuk checkbox metrics */
        .metrics-checkbox-container {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            max-width: 600px;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background-color: var(--bg-white);
        }

        .metric-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: var(--radius-md);
            background-color: var(--bg-light);
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
            user-select: none;
        }

        .metric-checkbox:hover {
            background-color: #e5f3ff;
            border-color: var(--primary-color);
        }

        .metric-checkbox-input {
            width: 16px;
            height: 16px;
            accent-color: var(--primary-color);
            cursor: pointer;
        }

        .metric-checkbox-label {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-primary);
            cursor: pointer;
        }

        .metric-checkbox-input:checked+.metric-checkbox-label {
            color: var(--primary-color);
            font-weight: 600;
        }

        .metric-checkbox-input:checked~.metric-checkbox {
            background-color: #e5f3ff;
            border-color: var(--primary-color);
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        /* Icon loading kecil untuk data real-time */
        .realtime-loading-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 50%;
            padding: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            opacity: 0;
            transform: scale(0.8);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .realtime-loading-indicator[wire\\:loading] {
            opacity: 1;
            transform: scale(1);
        }

        /* Tooltip untuk icon loading */
        .realtime-loading-indicator:hover::after {
            content: attr(title);
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 100%;
            margin-bottom: 8px;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1001;
        }

        /* Status indicator untuk real-time data */
        .realtime-status {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 50%;
            padding: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .status-dot-green {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: #10b981;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
            animation: pulse 2s infinite;
        }

        /* Tooltip untuk status indicator */
        .realtime-status:hover::after {
            content: attr(title);
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 100%;
            margin-bottom: 8px;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1001;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
            }

            50% {
                box-shadow: 0 0 16px rgba(16, 185, 129, 0.8);
            }

            100% {
                box-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
            }
        }

        .realtime-spinner {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid #10b981;
            border-radius: 50%;
            width: 16px;
            height: 16px;
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

        /* Gaya untuk single chart */
        .single-chart-container {
            position: relative;
            width: 100%;
            height: 390px;
            border-radius: var(--radius-lg);
            overflow: hidden;
            background-color: var(--bg-white);
            border: 1px solid var(--border-color);
        }

        /* KUNCI PERBAIKAN: Gaya untuk Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 28px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 28px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: var(--primary-color);
        }

        input:checked+.slider:before {
            transform: translateX(22px);
        }

        /* Styling untuk Select2 - Lebih sederhana dan efisien */

        /* Sembunyikan kursor yang berkedip di dalam kotak Select2 */
        .select2-search__field {
            caret-color: transparent;
        }

        .select2-container {
            width: 300px !important;
            min-width: 300px !important;
            max-width: 300px !important;
            flex-shrink: 0 !important;
            /* Pastikan container dapat menyesuaikan tinggi dengan konten */
            height: auto !important;
        }

        .select2-container--default .select2-selection--multiple {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background-color: var(--bg-white);
            /* Hapus pembatasan tinggi agar semua tag terlihat */
            min-height: 80px;
            height: auto !important;
            max-height: 80px;
            overflow-y: auto;
            padding: 0px 1px 1px 10px;
            /* Pastikan container dapat menyesuaikan dengan konten */
            /* display: flex; */
            /* align-items: flex-start; */
        }

        .select2-container--default .select2-selection--multiple:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }



        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: white;
            font-weight: bold;
            font-size: 14px;
            flex-shrink: 0;
            /* Mencegah ikon menyusut */

            /* INI KUNCINYA: Memberi jarak di sebelah kanan ikon "x" */
            margin-right: 8px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #fbbf24;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 16px;
            height: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .select2-dropdown {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background-color: var(--bg-white);
            box-shadow: var(--shadow-lg);
            z-index: 9999;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: var(--primary-color);
        }

        .select2-container--default .select2-selection--multiple .select2-selection__placeholder {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 4px;
            /* Pastikan semua tag terlihat tanpa terpotong */
            min-height: 32px;
            margin-bottom: -10px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            /* Ubah padding kiri untuk memberi ruang bagi ikon "x" */
            padding: 4px 2px 4px 20px;
            margin: 2px 0;
            font-size: 10px;
            line-height: 1.2;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            /* Tambahkan position: relative sebagai acuan untuk ikon "x" */
            position: relative;
        }

        /* Posisikan tombol 'Load More' di dalam area grafik */
        #load-more-container {
            position: absolute;
            top: 20px;
            /* Jarak dari atas area grafik */
            left: 20px;
            /* Jarak dari kiri area grafik */
            z-index: 10;
            /* Pastikan tombol di atas elemen grafik */
        }

        /* Styling khusus untuk tombol Load More agar tidak mengganggu grafik */
        #load-more-container .btn-secondary {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(4px);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
            font-size: 12px;
            padding: 6px 12px;
            border-radius: var(--radius-md);
        }

        #load-more-container .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 1);
            border-color: var(--primary-color);
        }
    </style>
    @livewireStyles
</head>

<body>
    <div class="dashboard-container">
        <div class="dashboard-title">
            <div class="header-frame">
                <div class="title-status-container">
                    <h1 class="main-title">Monitoring AWS - Analysis</h1>
                    <div class="status-indicator">
                        <div class="status-dot"></div>
                        <div class="status-text">System Connected</div>
                    </div>
                </div>
            </div>
            <div class="tabs">
                <div class="tab-item">
                    <div class="tab-text"><a href="{{ url('/') }}"
                            class="{{ request()->is('/') ? 'active' : '' }}">Overview</a></div>
                    <div class="tab-text"><a href="{{ url('/log-data') }}"
                            class="{{ request()->is('log-data') ? 'active' : '' }}">Log Data</a></div>
                    <div class="tab-text"><a href="{{ url('/analysis') }}"
                            class="{{ request()->is('analysis') ? 'active' : '' }}">Analysis Chart</a></div>
                </div>
            </div>
        </div>

        <div class="chart-page-container">
            {{ $slot }}
        </div>
    </div>

    {{-- Cukup muat library Plotly.js versi terbaru. --}}
    <script src="https://cdn.plot.ly/plotly-2.32.0.min.js"></script>
    {{-- tambahan buat dropdown select (graph-analysis) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    {{-- Livewire Scripts --}}
    @livewireScripts
</body>

</html>
