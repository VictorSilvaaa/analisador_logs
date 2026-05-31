<?php

use App\Http\Controllers\LogFileProcessingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Bem-vindo a API',
    ]);
});

Route::post('/logs/process', [LogFileProcessingController::class, 'process']);
