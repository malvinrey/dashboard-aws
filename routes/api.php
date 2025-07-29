<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReceiverController;

Route::post('/aws/receiver', [ReceiverController::class, 'store']);

use App\Http\Controllers\AnalysisController;

// Route untuk API data grafik
Route::get('/analysis-data', [AnalysisController::class, 'getAnalysisData']);
