<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\CarbonImmutable;
use App\Models\Agendamento;
use App\Models\Bloqueio;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\Servico;

class AgendamentoController extends Controller
{
    public function create(Request $request)
    {
        return view('agendamentos.create', [
            'data' => $request->input('data', now($this->tenant()->timezone)->toDateString()),
            'pacienteId' => $request->input('paciente_id'),
            'pacientes' => Paciente::orderBy('nome')->get(),
            'profissionais' => Profissional::where('ativo', true)->orderBy('nome')->get(),
            'servicos' => Servico::orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = $this->tenant()->id;

        $dados = $request->validate([
            'paciente_id' => ['required', Rule::exists('pacientes', 'id')->where('tenant_id', $tenantId)],
            'profissional_id' => ['required', Rule::exists('profissionais', 'id')->where('tenant_id', $tenantId)],
            'servico_id' => ['required', Rule::exists('servicos', 'id')->where('tenant_id', $tenantId)],
            'data' => 'required|date_format:Y-m-d',
            'hora' => 'required|date_format:H:i',
            'link_teleconsulta' => 'nullable|url|max:255',
            'convenio' => 'nullable|string|max:100',
            'notas_clinicas' => 'nullable|string|max:5000',
        ]);

        $servico = Servico::findOrFail($dados['servico_id']);
        $inicio = CarbonImmutable::createFromFormat('Y-m-d H:i', $dados['data'].' '.$dados['hora']);
        $fim = $inicio->addMinutes($servico->duracao_minutos);

        $bloqueado = Bloqueio::query()
            ->where(function ($query) use ($dados) {
                $query->whereNull('profissional_id')->orWhere('profissional_id', $dados['profissional_id']);
            })
            ->whereDate('data', '<=', $dados['data'])
            ->where(function ($query) use ($dados) {
                $query->whereDate('data_fim', '>=', $dados['data'])
                    ->orWhere(function ($query) use ($dados) {
                        $query->whereNull('data_fim')->whereDate('data', $dados['data']);
                    });
            })
            ->exists();

        $feriado = in_array(substr($dados['data'], 5), config('feriados.fixos'))
            || in_array($dados['data'], config('feriados.moveis'));

        if ($bloqueado || $feriado) {
            return back()->withInput()->withErrors(['data' => 'A agenda está bloqueada nesse dia.']);
        }

        $ocupado = Agendamento::where('profissional_id', $dados['profissional_id'])
            ->where('status', '!=', Agendamento::CANCELADO)
            ->where('inicio', '<', $fim)
            ->where('fim', '>', $inicio)
            ->exists();

        if ($ocupado) {
            return back()->withInput()->withErrors(['hora' => 'O profissional já tem um agendamento nesse horário.']);
        }

        Agendamento::create([
            'paciente_id' => $dados['paciente_id'],
            'profissional_id' => $dados['profissional_id'],
            'servico_id' => $dados['servico_id'],
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

    public function updateStatus(Request $request, $id)
    {
        $agendamento = Agendamento::findOrFail($id);

        $dados = $request->validate([
            'status' => 'required|in:'.implode(',', Agendamento::STATUS),
        ]);

        $agendamento->alterarStatus($dados['status']);

        return back()->with('sucesso', 'Status atualizado.');
    }
}
