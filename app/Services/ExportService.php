<?php

namespace App\Services;

use App\Models\ScadaDataWide;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ExportService
{
    /**
     * Export data to CSV format
     */
    public function exportToCsv(array $filters = []): string
    {
        $data = $this->getFilteredData($filters);

        $filename = 'scada_logs_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';
        $filePath = 'exports/' . $filename;

        // Create CSV content
        $csvContent = '';

        // Add CSV header
        $csvContent .= $this->arrayToCsv([
            'ID',
            'Timestamp',
            'Nama Group',
            'PAR Sensor',
            'Solar Radiation',
            'Wind Speed',
            'Wind Direction',
            'Temperature',
            'Humidity',
            'Pressure',
            'Rainfall'
        ]);

        // Add data rows
        foreach ($data as $row) {
            $csvContent .= $this->arrayToCsv([
                $row->id,
                $row->timestamp_device->format('Y-m-d H:i:s'),
                $row->nama_group,
                $row->par_sensor ?? '',
                $row->solar_radiation ?? '',
                $row->wind_speed ?? '',
                $row->wind_direction ?? '',
                $row->temperature ?? '',
                $row->humidity ?? '',
                $row->pressure ?? '',
                $row->rainfall ?? ''
            ]);
        }

        // Store file using Laravel Storage
        Storage::disk('public')->put($filePath, $csvContent);

        return $filename;
    }

    /**
     * Convert array to CSV string
     */
    private function arrayToCsv(array $data): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $data);
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        return $csv;
    }

    /**
     * Get filtered data for export
     */
    private function getFilteredData(array $filters)
    {
        $query = ScadaDataWide::query();

        // Apply date filters
        if (!empty($filters['startDate'])) {
            $query->whereDate('timestamp_device', '>=', $filters['startDate']);
        }
        if (!empty($filters['endDate'])) {
            $query->whereDate('timestamp_device', '<=', $filters['endDate']);
        }

        // Apply search filter
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('id', 'LIKE', "%{$filters['search']}%")
                    ->orWhere('nama_group', 'LIKE', "%{$filters['search']}%")
                    ->orWhere('par_sensor', 'LIKE', "%{$filters['search']}%")
                    ->orWhere('solar_radiation', 'LIKE', "%{$filters['search']}%")
                    ->orWhere('wind_speed', 'LIKE', "%{$filters['search']}%")
                    ->orWhere('wind_direction', 'LIKE', "%{$filters['search']}%")
                    ->orWhere('temperature', 'LIKE', "%{$filters['search']}%")
                    ->orWhere('humidity', 'LIKE', "%{$filters['search']}%")
                    ->orWhere('pressure', 'LIKE', "%{$filters['search']}%")
                    ->orWhere('rainfall', 'LIKE', "%{$filters['search']}%");
            });
        }

        // Apply sorting
        $sortField = $filters['sortField'] ?? 'id';
        $sortDirection = $filters['sortDirection'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        // Get all data (no limit for export)
        return $query->get();
    }

    /**
     * Get export statistics
     */
    public function getExportStats(array $filters = []): array
    {
        $data = $this->getFilteredData($filters);

        return [
            'total_records' => $data->count(),
            'date_range' => [
                'start' => $data->min('timestamp_device'),
                'end' => $data->max('timestamp_device'),
            ],
            'groups' => $data->pluck('nama_group')->unique()->count(),
            'export_time' => Carbon::now()->format('Y-m-d H:i:s')
        ];
    }
}
