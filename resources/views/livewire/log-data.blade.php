<div wire:poll.3s>
    {{-- Atribut wire:poll akan me-refresh komponen ini setiap 3 detik --}}

    <br>

    {{-- Filter Controls --}}
    <div class="filter-container">
        <div class="filter-row">
            {{-- Date Range Filters --}}
            <div class="filter-group">
                <label for="startDate">Start Date:</label>
                <input type="date" id="startDate" wire:model="startDate" class="filter-input">
            </div>

            <div class="filter-group">
                <label for="endDate">End Date:</label>
                <input type="date" id="endDate" wire:model="endDate" class="filter-input">
            </div>

            {{-- Search Filter --}}
            <div class="filter-group">
                <label for="search">Search:</label>
                <input type="search" id="search" wire:model.debounce.500ms="search"
                    placeholder="Search in all columns..." class="filter-input">
                @if ($search)
                    <small class="search-info">Searching for: "{{ $search }}"</small>
                @endif
            </div>
            <div class="filter-actions">
                <button wire:click="applyFilters" class="filter-button apply-button">
                    <span wire:loading.remove wire:target="applyFilters">Apply Filters</span>
                    <span wire:loading wire:target="applyFilters">Applying...</span>
                </button>
                <button wire:click="clearFilters" class="filter-button clear-button">
                    <span wire:loading.remove wire:target="clearFilters">Clear Filters</span>
                    <span wire:loading wire:target="clearFilters">Clearing...</span>
                </button>
                <button wire:click="exportCsv" class="filter-button export-button">
                    <span wire:loading.remove wire:target="exportCsv">
                        📊 Export Excel
                    </span>
                    <span wire:loading wire:target="exportCsv">
                        Exporting...
                    </span>
                </button>
            </div>
        </div>



        {{-- Filter Summary --}}
        @if ($startDate || $endDate || $search)
            <div class="filter-summary">
                <small>
                    <strong>Active Filters:</strong>
                    @if ($startDate)
                        Start: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                    @endif
                    @if ($endDate)
                        End: {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                    @endif
                    @if ($search)
                        Search: "{{ $search }}"
                    @endif
                    ({{ $totalRecords }} records found)
                </small>
            </div>
        @endif
    </div>

    <br>

    <table class="log-table">
        <thead>
            <tr>
                <th wire:click="sortBy('id')" class="sortable-header">
                    ID
                    @if ($sortField === 'id')
                        <span class="sort-icon">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
                <th wire:click="sortBy('timestamp_device')" class="sortable-header">
                    Waktu
                    @if ($sortField === 'timestamp_device')
                        <span class="sort-icon">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
                <th wire:click="sortBy('nama_group')" class="sortable-header">
                    Nama Grup
                    @if ($sortField === 'nama_group')
                        <span class="sort-icon">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
                <th wire:click="sortBy('par_sensor')" class="sortable-header">
                    PAR Sensor
                    @if ($sortField === 'par_sensor')
                        <span class="sort-icon">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
                <th wire:click="sortBy('solar_radiation')" class="sortable-header">
                    Solar Radiation
                    @if ($sortField === 'solar_radiation')
                        <span class="sort-icon">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
                <th wire:click="sortBy('wind_speed')" class="sortable-header">
                    Wind Speed
                    @if ($sortField === 'wind_speed')
                        <span class="sort-icon">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
                <th wire:click="sortBy('wind_direction')" class="sortable-header">
                    Wind Direction
                    @if ($sortField === 'wind_direction')
                        <span class="sort-icon">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
                <th wire:click="sortBy('temperature')" class="sortable-header">
                    Temperature
                    @if ($sortField === 'temperature')
                        <span class="sort-icon">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
                <th wire:click="sortBy('humidity')" class="sortable-header">
                    Humidity
                    @if ($sortField === 'humidity')
                        <span class="sort-icon">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
                <th wire:click="sortBy('pressure')" class="sortable-header">
                    Pressure
                    @if ($sortField === 'pressure')
                        <span class="sort-icon">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
                <th wire:click="sortBy('rainfall')" class="sortable-header">
                    Rainfall
                    @if ($sortField === 'rainfall')
                        <span class="sort-icon">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr wire:key="{{ $log->id }}">
                    <td>{{ $log->id }}</td>
                    <td>{{ $log->timestamp_device->format('d M Y, H:i:s') }}</td>
                    <td>{{ $log->nama_group }}</td>
                    <td class="{{ is_null($log->par_sensor) ? 'text-muted' : '' }}">
                        {{ $log->par_sensor ?? '-' }}
                    </td>
                    <td class="{{ is_null($log->solar_radiation) ? 'text-muted' : '' }}">
                        {{ $log->solar_radiation ?? '-' }}
                    </td>
                    <td class="{{ is_null($log->wind_speed) ? 'text-muted' : '' }}">
                        {{ $log->wind_speed ?? '-' }}
                    </td>
                    <td class="{{ is_null($log->wind_direction) ? 'text-muted' : '' }}">
                        {{ $log->wind_direction ?? '-' }}
                    </td>
                    <td class="{{ is_null($log->temperature) ? 'text-muted' : '' }}">
                        {{ $log->temperature ?? '-' }}
                    </td>
                    <td class="{{ is_null($log->humidity) ? 'text-muted' : '' }}">
                        {{ $log->humidity ?? '-' }}
                    </td>
                    <td class="{{ is_null($log->pressure) ? 'text-muted' : '' }}">
                        {{ $log->pressure ?? '-' }}
                    </td>
                    <td class="{{ is_null($log->rainfall) ? 'text-muted' : '' }}">
                        {{ $log->rainfall ?? '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align: center;">Belum ada data log yang tercatat.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <br>

    <div class="load-more-container">
        {{-- Tampilkan tombol hanya jika data yang ditampilkan lebih sedikit dari total data --}}
        @if ($amount < $totalRecords)
            <button wire:click="loadMore" wire:loading.attr="disabled" class="load-more-button">
                <span wire:loading.remove wire:target="loadMore">
                    Load More
                </span>
                <span wire:loading wire:target="loadMore">
                    Loading...
                </span>
            </button>
        @else
            <p style="text-align: center; color: #777;">Sudah menampilkan semua data.</p>
        @endif
    </div>

    <style>
        {{-- Filter Styles --}} .filter-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .filter-row {
            display: flex;
            gap: 20px;
            align-items: end;
            margin-bottom: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 150px;
        }

        .filter-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
            font-size: 0.9em;
        }

        .filter-input {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 0.9em;
            transition: border-color 0.3s;
        }

        .filter-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }

        .search-info {
            color: #007bff;
            font-size: 0.8em;
            margin-top: 2px;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
        }

        .filter-button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 500;
            transition: background-color 0.3s;
            min-width: 120px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
        }

        .apply-button {
            background: #007bff;
            color: white;
        }

        .apply-button:hover {
            background: #0056b3;
        }

        .clear-button {
            background: #6c757d;
            color: white;
        }

        .clear-button:hover {
            background: #545b62;
        }

        .export-button {
            background: #28a745;
            color: white;
        }

        .export-button:hover {
            background: #218838;
        }

        {{-- Export Notification Styles --}} .export-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
        }

        .notification-content {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .notification-icon {
            font-size: 1.2em;
        }

        .notification-message {
            flex: 1;
            color: #155724;
            font-size: 0.9em;
        }

        .notification-download {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.8em;
            transition: background-color 0.3s;
        }

        .notification-download:hover {
            background: #218838;
            color: white;
        }

        .notification-close {
            background: none;
            border: none;
            font-size: 1.5em;
            color: #6c757d;
            cursor: pointer;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification-close:hover {
            color: #495057;
        }

        .filter-summary {
            margin-top: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 4px solid #007bff;
        }

        .filter-summary small {
            color: #495057;
        }

        {{-- Sortable Header Styles --}} .sortable-header {
            cursor: pointer;
            user-select: none;
            position: relative;
            transition: background-color 0.3s;
        }

        .sortable-header:hover {
            background: #e9ecef !important;
        }

        .sort-icon {
            margin-left: 5px;
            font-weight: bold;
            color: #007bff;
        }

        .log-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9em;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .log-table th {
            background: #f8f9fa;
            padding: 12px 8px;
            text-align: middle;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }

        .log-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }

        .log-table tr:hover {
            background-color: #f8f9fa;
        }

        .text-muted {
            color: #6c757d;
            font-style: italic;
        }

        .load-more-container {
            text-align: center;
            margin-top: 20px;
        }

        .load-more-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .load-more-button:hover {
            background: #0056b3;
        }

        .load-more-button:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        /* Responsive design */
        @media (max-width: 1200px) {
            .log-table {
                font-size: 0.8em;
            }

            .log-table th,
            .log-table td {
                padding: 8px 6px;
            }

            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                min-width: auto;
            }
        }

        @media (max-width: 768px) {
            .log-table {
                font-size: 0.7em;
            }

            .log-table th,
            .log-table td {
                padding: 6px 4px;
            }

            .filter-container {
                padding: 15px;
            }

            .filter-actions {
                flex-direction: column;
            }
        }
    </style>
</div>
