<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ScadaDataService; // <-- Impor service baru
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReceiverController extends Controller
{
    // Gunakan dependency injection untuk "memanggil" service
    public function __construct(protected ScadaDataService $scadaDataService) {}

    public function store(Request $request)
    {
        // Aktifkan logging untuk melihat payload yang masuk
        // Log::info('Incoming SCADA Payload:', $request->all());

        $validator = Validator::make($request->all(), [
            'DataArray' => 'required|array|min:1|max:10000', // Batasi maksimal 10k data per request
            'DataArray.*._groupTag' => 'required|string|max:255',
            'DataArray.*._terminalTime' => 'required|date',
            'DataArray.*.temperature' => 'nullable|numeric|between:-50,100',
            'DataArray.*.humidity' => 'nullable|numeric|between:0,100',
            'DataArray.*.pressure' => 'nullable|numeric|between:800,1200',
            'DataArray.*.rainfall' => 'nullable|numeric|min:0',
            'DataArray.*.wind_speed' => 'nullable|numeric|min:0',
            'DataArray.*.wind_direction' => 'nullable|numeric|between:0,360',
            'DataArray.*.par_sensor' => 'nullable|numeric|min:0',
            'DataArray.*.solar_radiation' => 'nullable|numeric|min:0',
        ], [
            'DataArray.max' => 'Payload terlalu besar. Maksimal 10.000 data per request.',
            'DataArray.*._groupTag.required' => 'Group tag wajib diisi untuk setiap data.',
            'DataArray.*._terminalTime.required' => 'Timestamp wajib diisi untuk setiap data.',
            'DataArray.*.temperature.between' => 'Suhu harus antara -50°C sampai 100°C.',
            'DataArray.*.humidity.between' => 'Kelembaban harus antara 0% sampai 100%.',
            'DataArray.*.pressure.between' => 'Tekanan harus antara 800-1200 hPa.',
            'DataArray.*.wind_direction.between' => 'Arah angin harus antara 0° sampai 360°.',
        ]);

        if ($validator->fails()) {
            Log::warning('SCADA Payload validation failed', [
                'errors' => $validator->errors(),
                'payload_sample' => array_slice($request->input('DataArray', []), 0, 3)
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Data validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $startTime = microtime(true);

            // Cukup panggil satu method dari service untuk melakukan semua pekerjaan
            $this->scadaDataService->processScadaPayload($request->all());

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('SCADA Payload processed successfully', [
                'processing_time_ms' => $processingTime,
                'data_count' => count($request->input('DataArray', [])),
                'timestamp' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Data successfully received and processed.',
                'processing_time_ms' => $processingTime,
                'data_count' => count($request->input('DataArray', []))
            ], 201);
        } catch (\Exception $e) {
            Log::error('SCADA Payload processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload_size' => count($request->all()),
                'timestamp' => now()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Server error while processing data.',
                'error_code' => 'PROCESSING_ERROR'
            ], 500);
        }
    }
}
