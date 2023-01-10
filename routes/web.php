<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
})->name('health');

Route::get('/login', 'Auth\LoginController@create')->name('login')->middleware('guest');
Route::post('/login', 'Auth\LoginController@store')->middleware('guest');
Route::post('/logout', 'Auth\LoginController@destroy')->name('logout')->middleware('auth');

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::redirect('/', '/agenda');

    Route::get('/agenda', 'AgendaController@index')->name('agenda');

    Route::get('/agendamentos/novo', 'AgendamentoController@create')->name('agendamentos.create');
    Route::post('/agendamentos', 'AgendamentoController@store')->name('agendamentos.store');
    Route::patch('/agendamentos/{id}/status', 'AgendamentoController@updateStatus')->name('agendamentos.status');

    Route::post('/agendamentos/{id}/anexos', 'AnexoController@store')->name('anexos.store');
    Route::get('/anexos/{id}', 'AnexoController@show')->name('anexos.show');

    Route::get('/pacientes', 'PacienteController@index')->name('pacientes.index');
    Route::get('/pacientes/busca', 'PacienteController@busca')->name('pacientes.busca');
    Route::get('/pacientes/{id}', 'PacienteController@show')->name('pacientes.show');
    Route::post('/pacientes', 'PacienteController@store')->name('pacientes.store');

    Route::get('/profissionais', 'ProfissionalController@index')->name('profissionais.index');
});
