<?php

use App\Http\Controllers\Api\V1\AgendamentoController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HorarioController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/token', [AuthController::class, 'store']);

    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::get('/horarios', [HorarioController::class, 'index']);
        Route::get('/agendamentos', [AgendamentoController::class, 'index']);
        Route::post('/agendamentos', [AgendamentoController::class, 'store']);
        Route::delete('/agendamentos/{id}', [AgendamentoController::class, 'destroy'])->whereNumber('id');
    });
});
