<?php

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Profissional;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendaController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'data' => ['nullable', 'date_format:Y-m-d'],
            'profissional_id' => ['nullable', 'integer'],
        ]);

        $data = $request->filled('data')
            ? CarbonImmutable::createFromFormat('Y-m-d', $request->input('data'))->startOfDay()
            : $this->tenant()->agora()->startOfDay();

        $agendamentos = Agendamento::with(['paciente', 'profissional', 'servico', 'anexos'])
            ->whereDate('inicio', $data->toDateString())
            ->when($request->filled('profissional_id'), fn ($query) => $query->where('profissional_id', $request->integer('profissional_id')))
            ->orderBy('inicio')
            ->get();

        return view('agenda.index', [
            'data' => $data,
            'agendamentos' => $agendamentos,
            'resumo' => $agendamentos->countBy('status'),
            'profissionais' => Profissional::where('ativo', true)->orderBy('nome')->get(),
            'profissionalId' => $request->integer('profissional_id') ?: null,
        ]);
    }
}
