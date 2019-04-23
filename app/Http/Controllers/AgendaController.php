<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Agendamento;

class AgendaController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'data' => 'nullable|date_format:Y-m-d',
        ]);

        $data = Carbon::parse(array_get($filtros, 'data') ?: date('Y-m-d'))->startOfDay();

        $agendamentos = Agendamento::with(['paciente', 'profissional', 'servico'])
            ->whereDate('inicio', $data->toDateString())
            ->orderBy('inicio')
            ->get();

        return view('agenda.index', [
            'data' => $data,
            'agendamentos' => $agendamentos,
        ]);
    }
}
