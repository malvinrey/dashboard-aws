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
    public function __construct(protected ScadaDataService $scadaDataService)
    {
    }

    public function store(Request $request)
    {
        // Aktifkan logging untuk melihat payload yang masuk
        // Log::info('Incoming SCADA Payload:', $request->all());

        $validator = Validator::make($request->all(), [
            'DataArray' => 'required|array|min:1',
            'DataArray.*._groupTag' => 'required|string',
            'DataArray.*._terminalTime' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            // Cukup panggil satu method dari service untuk melakukan semua pekerjaan
            $this->scadaDataService->processScadaPayload($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Data successfully received and processed.'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while processing data.'
            ], 500);
        }
    }
}
