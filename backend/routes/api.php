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

Route::get('/metrics', function () {
    // Hitung total request (ditambah 1 setiap kali endpoint/aplikasi diakses)
    $requestsTotal = Cache::increment('app_http_requests_total');
    
    // Cek status hidup layanan (1 = UP, 0 = DOWN)
    $dbUp = 1;
    try {
        DB::connection()->getPdo();
    } catch (\Exception $e) {
        $dbUp = 0;
    }

    $metrics = <<<EOT
# HELP film_uts_http_requests_total Total HTTP requests handled by FilmKu backend
# TYPE film_uts_http_requests_total counter
film_uts_http_requests_total {$requestsTotal}

# HELP film_uts_database_up Database availability status (1 = up, 0 = down)
# TYPE film_uts_database_up gauge
film_uts_database_up {$dbUp}
EOT;

    return response($metrics . "\n", 200)
        ->header('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
});