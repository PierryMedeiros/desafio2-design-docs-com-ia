<?php

namespace App\Http\Controllers;

use App\Agenda\Disponibilidade;
use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\Servico;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgendamentoController extends Controller
{
    public function create(Request $request): View
    {
        return view('agendamentos.create', [
            'data' => $request->input('data', $this->tenant()->agora()->toDateString()),
            'pacienteId' => $request->input('paciente_id'),
            'pacientes' => Paciente::orderBy('nome')->get(),
            'profissionais' => Profissional::where('ativo', true)->orderBy('nome')->get(),
            'servicos' => Servico::orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request, Disponibilidade $disponibilidade): RedirectResponse
    {
        $tenantId = $this->tenant()->id;

        $dados = $request->validate([
            'paciente_id' => ['required', Rule::exists('pacientes', 'id')->where('tenant_id', $tenantId)],
            'profissional_id' => ['required', Rule::exists('profissionais', 'id')->where('tenant_id', $tenantId)],
            'servico_id' => ['required', Rule::exists('servicos', 'id')->where('tenant_id', $tenantId)],
            'data' => ['required', 'date_format:Y-m-d'],
            'hora' => ['required', 'date_format:H:i'],
            'link_teleconsulta' => ['nullable', 'url', 'max:255'],
            'convenio' => ['nullable', 'string', 'max:100'],
            'notas_clinicas' => ['nullable', 'string', 'max:5000'],
        ]);

        $servico = Servico::findOrFail($dados['servico_id']);
        $profissional = Profissional::findOrFail($dados['profissional_id']);
        $inicio = CarbonImmutable::createFromFormat('Y-m-d H:i', $dados['data'].' '.$dados['hora']);
        $fim = $inicio->addMinutes($servico->duracao_minutos);

        if ($disponibilidade->diaBloqueado($profissional, $inicio)) {
            return back()->withInput()->withErrors(['data' => 'A agenda está bloqueada nesse dia.']);
        }

        if ($disponibilidade->conflita($profissional->id, $inicio, $fim)) {
            return back()->withInput()->withErrors(['hora' => 'O profissional já tem um agendamento nesse horário.']);
        }

        Agendamento::create([
            'paciente_id' => $dados['paciente_id'],
            'profissional_id' => $profissional->id,
            'servico_id' => $servico->id,
            'inicio' => $inicio,
            'fim' => $fim,
            'status' => Agendamento::AGENDADO,
            'link_teleconsulta' => $dados['link_teleconsulta'] ?? null,
            'convenio' => $dados['convenio'] ?? null,
            'notas_clinicas' => $dados['notas_clinicas'] ?? null,
        ]);

        return redirect()
            ->route('agenda', ['data' => $inicio->toDateString()])
            ->with('sucesso', 'Agendamento criado.');
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $agendamento = Agendamento::findOrFail($id);

        $dados = $request->validate([
            'status' => ['required', Rule::in(Agendamento::STATUS)],
        ]);

        $agendamento->alterarStatus($dados['status']);

        return back()->with('sucesso', 'Status atualizado.');
    }
}
