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

Route::get('/login', 'Auth\LoginController@create')->name('login')->middleware('guest');
Route::post('/login', 'Auth\LoginController@store')->middleware('guest');
Route::post('/logout', 'Auth\LoginController@destroy')->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::redirect('/', '/agenda');

    Route::get('/agenda', 'AgendaController@index')->name('agenda');

    Route::get('/agendamentos/novo', 'AgendamentoController@create')->name('agendamentos.create');
    Route::post('/agendamentos', 'AgendamentoController@store')->name('agendamentos.store');

    Route::get('/pacientes', 'PacienteController@index')->name('pacientes.index');
    Route::post('/pacientes', 'PacienteController@store')->name('pacientes.store');

    Route::get('/profissionais', 'ProfissionalController@index')->name('profissionais.index');
});
