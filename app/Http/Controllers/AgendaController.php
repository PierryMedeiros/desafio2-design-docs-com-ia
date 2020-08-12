<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Carbon\CarbonImmutable;
use App\Models\Agendamento;
use App\Models\Profissional;

class AgendaController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'data' => 'nullable|date_format:Y-m-d',
            'profissional_id' => 'nullable|integer',
        ]);

        $data = CarbonImmutable::parse(Arr::get($filtros, 'data') ?: date('Y-m-d'))->startOfDay();

        $agendamentos = Agendamento::with(['paciente', 'profissional', 'servico', 'anexos'])
            ->whereDate('inicio', $data->toDateString())
            ->when($request->input('profissional_id'), function ($query, $profissionalId) {
                return $query->where('profissional_id', $profissionalId);
            })
            ->orderBy('inicio')
            ->get();

        return view('agenda.index', [
            'data' => $data,
            'agendamentos' => $agendamentos,
            'profissionais' => Profissional::where('ativo', true)->orderBy('nome')->get(),
            'profissionalId' => (int) $request->input('profissional_id') ?: null,
        ]);
    }
}
