<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\Servico;

class AgendamentoController extends Controller
{
    public function create(Request $request)
    {
        return view('agendamentos.create', [
            'data' => $request->input('data', date('Y-m-d')),
            'pacientes' => Paciente::orderBy('nome')->get(),
            'profissionais' => Profissional::where('ativo', true)->orderBy('nome')->get(),
            'servicos' => Servico::orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'paciente_id' => 'required|exists:pacientes,id',
            'profissional_id' => 'required|exists:profissionais,id',
            'servico_id' => 'required|exists:servicos,id',
            'data' => 'required|date_format:Y-m-d',
            'hora' => 'required|date_format:H:i',
            'hora_fim' => 'required|date_format:H:i|after:hora',
        ]);

        $inicio = Carbon::createFromFormat('Y-m-d H:i', $dados['data'].' '.$dados['hora']);
        $fim = Carbon::createFromFormat('Y-m-d H:i', $dados['data'].' '.$dados['hora_fim']);

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

        $agendamento->status = $dados['status'];
        $agendamento->save();

        return back()->with('sucesso', 'Status atualizado.');
    }
}
