<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalysisController;
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

Route::get('/analysis', [AnalysisController::class, 'index'])->name('analysis');
