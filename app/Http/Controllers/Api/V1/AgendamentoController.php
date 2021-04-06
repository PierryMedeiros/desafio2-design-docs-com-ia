<?php
namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;
use App\Http\Controllers\Controller;
use App\Http\Resources\AgendamentoResource;
use App\Models\Agendamento;
use App\Models\Profissional;
use App\Models\Servico;

class AgendamentoController extends Controller
{
    public function index(Request $request)
    {
        $agendamentos = $request->user()
            ->agendamentos()
            ->orderByDesc('inicio')
            ->get();

        return AgendamentoResource::collection($agendamentos);
    }

    public function store(Request $request)
    {
        $paciente = $request->user();

        $dados = $request->validate([
            'profissional_id' => 'required|integer',
            'servico_id' => 'required|integer',
            'inicio' => 'required|date_format:Y-m-d H:i',
        ]);

        $servico = Servico::findOrFail($dados['servico_id']);
        $inicio = CarbonImmutable::createFromFormat('Y-m-d H:i', $dados['inicio']);
        $fim = $inicio->addMinutes($servico->duracao_minutos);

        return DB::transaction(function () use ($dados, $paciente, $servico, $inicio, $fim) {
            $profissional = Profissional::where('ativo', true)->lockForUpdate()->findOrFail($dados['profissional_id']);

            $ocupado = Agendamento::where('profissional_id', $profissional->id)
                ->where('status', '!=', Agendamento::CANCELADO)
                ->where('inicio', '<', $fim)
                ->where('fim', '>', $inicio)
                ->exists();

            if ($ocupado) {
                return response()->json(['message' => 'Esse horário acabou de ser ocupado.'], 409);
            }

            $agendamento = $paciente->agendamentos()->create([
                'profissional_id' => $profissional->id,
                'servico_id' => $servico->id,
                'inicio' => $inicio,
                'fim' => $fim,
                'status' => Agendamento::AGENDADO,
            ]);

            return (new AgendamentoResource($agendamento))
                ->response()
                ->setStatusCode(201);
        });
    }

    public function destroy(Request $request, $id)
    {
        $agendamento = Agendamento::findOrFail($id);

        abort_if($agendamento->paciente_id !== $request->user()->id, 403);

        $agendamento->status = Agendamento::CANCELADO;
        $agendamento->save();

        return response()->noContent();
    }
}
