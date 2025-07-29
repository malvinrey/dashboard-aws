<?php

namespace App\Http\Controllers;

use App\Services\ScadaDataService; // <-- Impor service baru
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected ScadaDataService $scadaDataService) {}

    public function index()
    {
        // Cukup panggil satu method dari service untuk mendapatkan data
        $dashboardData = $this->scadaDataService->getDashboardMetrics();

        // Kirim data yang sudah diolah ke view
        return view('views-dashboard', $dashboardData);
    }

    /**
     * Menampilkan halaman log data
     */
    public function logData()
    {
        return view('views-log-data');
    }
}
