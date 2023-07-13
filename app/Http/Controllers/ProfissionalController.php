<?php

namespace App\Http\Controllers;

use App\Models\Profissional;
use Illuminate\View\View;

class ProfissionalController extends Controller
{
    public function index(): View
    {
        $profissionais = Profissional::with(['disponibilidades' => fn ($query) => $query->orderBy('dia_semana')->orderBy('hora_inicio')])
            ->orderBy('nome')
            ->get();

        return view('profissionais.index', ['profissionais' => $profissionais]);
    }
}
