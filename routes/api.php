<?php
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->namespace('Api\V1')->group(function () {
    Route::post('/auth/token', 'AuthController@store');

    Route::middleware(['tenant.schema', 'auth:sanctum'])->group(function () {
        Route::get('/horarios', 'HorarioController@index');
        Route::get('/agendamentos', 'AgendamentoController@index');
        Route::post('/agendamentos', 'AgendamentoController@store');
    });
});
