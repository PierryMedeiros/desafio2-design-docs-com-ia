<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AgendamentoController;
use App\Http\Controllers\AnexoController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ListaEsperaController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\ProfissionalController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('health');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::redirect('/', '/agenda');

    Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda');

    Route::get('/agendamentos/novo', [AgendamentoController::class, 'create'])->name('agendamentos.create');
    Route::post('/agendamentos', [AgendamentoController::class, 'store'])->name('agendamentos.store');
    Route::patch('/agendamentos/{id}/status', [AgendamentoController::class, 'updateStatus'])->whereNumber('id')->name('agendamentos.status');

    Route::post('/agendamentos/{id}/anexos', [AnexoController::class, 'store'])->whereNumber('id')->name('anexos.store');
    Route::get('/anexos/{id}', [AnexoController::class, 'show'])->whereNumber('id')->name('anexos.show');

    Route::get('/pacientes', [PacienteController::class, 'index'])->name('pacientes.index');
    Route::get('/pacientes/busca', [PacienteController::class, 'busca'])->name('pacientes.busca');
    Route::get('/pacientes/{id}', [PacienteController::class, 'show'])->whereNumber('id')->name('pacientes.show');
    Route::post('/pacientes', [PacienteController::class, 'store'])->name('pacientes.store');

    Route::get('/profissionais', [ProfissionalController::class, 'index'])->name('profissionais.index');
    Route::get('/lista-espera', [ListaEsperaController::class, 'index'])->name('lista-espera.index');
});
