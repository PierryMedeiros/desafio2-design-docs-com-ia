<?php

namespace App\Http\Controllers;

use App\Models\Profissional;

class ProfissionalController extends Controller
{
    public function index()
    {
        $profissionais = Profissional::with('disponibilidades')->orderBy('nome')->get();

        return view('profissionais.index', ['profissionais' => $profissionais]);
    }
}
