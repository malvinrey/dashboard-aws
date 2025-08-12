<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReceiverController;

Route::post('/aws/receiver', [ReceiverController::class, 'store']);

use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\SseController;

// Route untuk API data grafik
Route::get('/analysis-data', [AnalysisController::class, 'getAnalysisData']);

// Endpoint baru untuk data real-time
Route::get('/latest-data', [AnalysisController::class, 'getLatestDataApi']);

// SSE endpoints untuk streaming real-time
Route::get('/sse/stream', [SseController::class, 'stream']);
Route::get('/sse/test', [SseController::class, 'test']);
