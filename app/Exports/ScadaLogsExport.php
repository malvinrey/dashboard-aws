<?php

namespace App\Exports;

use App\Models\ScadaDataWide;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ScadaLogsExport implements FromQuery, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function query()
    {
        // Gunakan FromQuery untuk efisiensi memori pada data besar
        $query = ScadaDataWide::query();
        $filters = $this->filters;

        // Terapkan filter yang sama seperti di ExportService
        if (!empty($filters['startDate'])) {
            $query->whereDate('timestamp_device', '>=', $filters['startDate']);
        }
        if (!empty($filters['endDate'])) {
            $query->whereDate('timestamp_device', '<=', $filters['endDate']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                    ->orWhere('nama_group', 'LIKE', "%{$search}%")
                    // Tambahkan kolom lain jika perlu
                    ->orWhere('temperature', 'LIKE', "%{$search}%");
            });
        }

        $query->orderBy($filters['sortField'] ?? 'id', $filters['sortDirection'] ?? 'desc');

        return $query;
    }

    public function headings(): array
    {
        // Definisikan header kolom Anda
        return [
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
        ];
    }

    public function map($log): array
    {
        // Definisikan data untuk setiap baris
        return [
            $log->id,
            $log->timestamp_device->format('Y-m-d H:i:s'),
            $log->nama_group,
            $log->par_sensor,
            $log->solar_radiation,
            $log->wind_speed,
            $log->wind_direction,
            $log->temperature,
            $log->humidity,
            $log->pressure,
            $log->rainfall,
        ];
    }
}
