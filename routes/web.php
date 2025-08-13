<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\PerformanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route utama mengarah ke dashboard
Route::get('/', [DashboardController::class, 'index'])->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/log-data', [DashboardController::class, 'logData'])->name('log-data');

use App\Livewire\AnalysisChart;

Route::get('/analysis', AnalysisChart::class);

// Route untuk mengakses file export
Route::get('/export/download/{filename}', [ExportController::class, 'download'])->name('export.download');

// Performance monitoring routes
Route::prefix('performance')->name('performance.')->group(function () {
    Route::get('/', [PerformanceController::class, 'index'])->name('dashboard');
    Route::get('/metrics', [PerformanceController::class, 'getMetrics'])->name('metrics');
    Route::get('/recommendations', [PerformanceController::class, 'getOptimizationRecommendations'])->name('recommendations');
    Route::post('/clear-cache', [PerformanceController::class, 'clearCache'])->name('clear-cache');
});

// Broadcasting routes (akan di-register otomatis oleh AppServiceProvider)
// Route::get('/broadcasting/auth', [BroadcastingController::class, 'authenticate']);

// WebSocket test routes
Route::get('/websocket-test', function () {
    return view('websocket-test');
})->name('websocket-test');

Route::get('/broadcast-test', function () {
    // Test broadcasting
    broadcast(new \App\Events\ScadaDataReceived([
        'test' => true,
        'message' => 'Test broadcast from route',
        'timestamp' => now()->toISOString()
    ], 'scada-test'));

    return response()->json(['message' => 'Test broadcast sent']);
})->name('broadcast-test');
