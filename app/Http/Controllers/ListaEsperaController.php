<?php

namespace App\Http\Controllers;

use App\Models\ListaEspera;
use Illuminate\View\View;

class ListaEsperaController extends Controller
{
    public function index(): View
    {
        $entradas = ListaEspera::with(['paciente', 'profissional', 'servico'])
            ->orderBy('data_desejada')
            ->get();

        return view('lista-espera.index', ['entradas' => $entradas]);
    }
}
