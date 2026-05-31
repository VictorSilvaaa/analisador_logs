<?php

use App\Http\Controllers\LogFileProcessingController;
use App\Http\Controllers\RequestLogReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Bem-vindo a API',
    ]);
});

Route::post('/logs/process', [LogFileProcessingController::class, 'process']);

Route::prefix('reports')->group(function (): void {
    Route::get('/requests-by-consumer', [RequestLogReportController::class, 'requestsByConsumer']);
    Route::get('/requests-by-service', [RequestLogReportController::class, 'requestsByService']);
    Route::get('/average-latencies-by-service', [RequestLogReportController::class, 'averageLatenciesByService']);
});
