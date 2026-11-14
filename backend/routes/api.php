<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FilmController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::apiResource('films', FilmController::class);
});

Route::get('/health', function (Request $request) {
    $status = [
        'status' => 'healthy',
        'services' => [
            'database' => 'ok',
            'cache' => 'ok',
        ],
    ];
    $httpCode = 200;

    // 1. Cek Koneksi Database
    try {
        DB::connection()->getPdo();
    } catch (\Exception $e) {
        $status['status'] = 'unhealthy';
        $status['services']['database'] = 'error: gagal terhubung ke database';
        $httpCode = 503;

        // LOG 1 (ERROR): Gagal menghubungi basis data
        Log::error('Healthcheck gagal pada layanan database', [
            'actor' => $request->ip() ?? 'docker-healthcheck',
            'action' => 'pengecekan_koneksi_database',
            'reason' => 'Koneksi PDO ke database ditolak atau terputus',
        ]);
    }

    // 2. Cek Koneksi Cache
    try {
        Cache::put('health_check_key', 'ok', 10);
        if (Cache::get('health_check_key') !== 'ok') {
            throw new \Exception('Nilai cache tidak sesuai');
        }
    } catch (\Exception $e) {
        $status['status'] = 'unhealthy';
        $status['services']['cache'] = 'error: gagal membaca/menulis cache';
        $httpCode = 503;

        // LOG 2 (ERROR): Gagal menghubungi cache
        Log::error('Healthcheck gagal pada layanan cache', [
            'actor' => $request->ip() ?? 'docker-healthcheck',
            'action' => 'pengecekan_sistem_cache',
            'reason' => 'Driver cache tidak merespons operasi tulis/baca',
        ]);
    }

    return response()->json($status, $httpCode);
});