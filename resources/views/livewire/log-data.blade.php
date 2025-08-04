<div wire:poll.3s>
    {{-- Atribut wire:poll akan me-refresh komponen ini setiap 3 detik --}}

    <br>

    <table class="log-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Waktu</th>
                <th>Nama Grup</th>
                <th>PAR Sensor</th>
                <th>Solar Radiation</th>
                <th>Wind Speed</th>
                <th>Wind Direction</th>
                <th>Temperature</th>
                <th>Humidity</th>
                <th>Pressure</th>
                <th>Rainfall</th>
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
        }

        @media (max-width: 768px) {
            .log-table {
                font-size: 0.7em;
            }

            .log-table th,
            .log-table td {
                padding: 6px 4px;
            }
        }
    </style>
</div>
