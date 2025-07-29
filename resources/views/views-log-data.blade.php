<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCADA Log Viewer (Load More)</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            /* Margin dihilangkan dari body */
            background-color: #f4f6f9;
            color: #333;
        }

        .dashboard-container {
            padding: 24px;
            /* Memberi padding ke seluruh konten halaman */
        }

        .log-table {
            /* Properti yang sudah ada */
            border-collapse: collapse;
            width: auto;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            background-color: white;
            border-radius: 2px;
            overflow: hidden;

            /* TAMBAHKAN DUA BARIS INI */
            margin-left: auto;
            margin-right: auto;
        }

        .log-table th,
        .log-table td {
            border-bottom: 1px solid #e9ecef;
            padding: 12px 15px;
            text-align: center;
        }

        .log-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }

        .log-table tr:hover {
            background-color: #f1f3f5;
        }

        .batch-separator td {
            border-bottom: 3px solid #333;
            /* Garis tebal berwarna gelap */
            padding: 2px 0 !important;
            /* Perkecil padding agar garisnya rapat */
        }

        .batch-separator {
            background-color: #f8f9fa;
            /* Warna latar belakang untuk baris pemisah */
        }

        .load-more-container {
            text-align: center;
            padding: 20px 0;
        }

        .load-more-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 1em;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .load-more-button:hover {
            background-color: #0056b3;
        }

        .load-more-button:disabled {
            background-color: #6c757d;
            cursor: wait;
        }

        /* --- CSS TAMBAHAN UNTUK HEADER --- */
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
            color: #1f2937;
            font-weight: 700;
            font-size: 32px;
            margin: 0;
        }

        .status-indicator {
            display: flex;
            padding: 8px 16px;
            justify-content: center;
            align-items: center;
            gap: 8px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            background-color: #fff;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #28a745;
            /* Green */
        }

        .status-text {
            color: #495057;
            font-weight: 500;
            font-size: 14px;
        }

        .tabs {
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 24px;
        }

        .tab-item {
            display: inline-flex;
            /* Use inline-flex for multiple tabs */
            gap: 24px;
        }

        .tab-text a {
            padding: 12px 4px;
            display: inline-block;
            text-decoration: none;
            color: #6c757d;
            font-weight: 500;
            font-size: 16px;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease-in-out;
        }

        .tab-text a.active,
        .tab-text a:hover {
            color: #007bff;
            border-bottom: 2px solid #007bff;
        }

        .section-title {
            color: #343a40;
            font-weight: 500;
            font-size: 24px;
            margin-bottom: 16px;
        }

        @media (max-width: 1200px) {
            .metric-grid-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 16px;
            }

            .metric-grid-container {
                grid-template-columns: 1fr;
            }

            .header-frame {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
        }
    </style>

    @livewireStyles
</head>

<body>
    <div class="dashboard-container">
        <!-- Header Section -->
        <div class="dashboard-title">
            <div class="header-frame">
                <div class="title-status-container">
                    <h1 class="main-title">Monitoring AWS - Log Data</h1>
                    <div class="status-indicator">
                        <div class="status-dot"></div>
                        <div class="status-text">System Connected</div>
                    </div>
                </div>
                {{-- Tombol log bisa ditambahkan di sini jika perlu --}}
            </div>
            <div class="tabs">
                <div class="tab-item">
                    <div class="tab-text">
                        {{-- Menambahkan class 'active' jika URL-nya cocok --}}
                        <a href="{{ url('/') }}" class="{{ request()->is('/') ? 'active' : '' }}">Overview</a>
                    </div>
                    <div class="tab-text">
                        <a href="{{ url('/log-data') }}" class="{{ request()->is('log-data') ? 'active' : '' }}">Log
                            Data</a>
                    </div>
                    <div class="tab-text">
                        <a href="{{ url('/analysis') }}"
                            class="{{ request()->is('analysis') ? 'active' : '' }}">Analysis Chart</a>
                    </div>
                </div>
            </div>
        </div>
        {{-- Memanggil komponen Livewire untuk ditampilkan di sini --}}
        <livewire:scada-log-table />

        @livewireScripts
</body>

</html>
