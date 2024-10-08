<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgendamentoResource;
use App\Models\Agendamento;
use App\Models\Profissional;
use App\Models\Servico;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgendamentoController extends Controller
{
    public function index(Request $request)
    {
        $agendamentos = $request->user()
            ->agendamentos()
            ->with(['profissional', 'servico'])
            ->orderByDesc('inicio')
            ->paginate(20);

        return AgendamentoResource::collection($agendamentos);
    }

    public function store(Request $request)
    {
        $paciente = $request->user();

        $dados = $request->validate([
            'profissional_id' => 'required|integer',
            'servico_id' => 'required|integer',
            'inicio' => 'required|date_format:Y-m-d H:i',
            'convenio' => 'nullable|string|max:100',
            'reagendar_de' => 'nullable|integer',
        ]);

        $anterior = isset($dados['reagendar_de'])
            ? $paciente->agendamentos()->whereIn('status', [Agendamento::AGENDADO, Agendamento::CONFIRMADO])->findOrFail($dados['reagendar_de'])
            : null;

        $servico = Servico::findOrFail($dados['servico_id']);
        $inicio = CarbonImmutable::createFromFormat('Y-m-d H:i', $dados['inicio']);
        $fim = $inicio->addMinutes($servico->duracao_minutos);

        return DB::transaction(function () use ($dados, $paciente, $servico, $inicio, $fim, $anterior) {
            $profissional = Profissional::where('ativo', true)->lockForUpdate()->findOrFail($dados['profissional_id']);

            $ocupado = Agendamento::where('profissional_id', $profissional->id)
                ->where('status', '!=', Agendamento::CANCELADO)
                ->when($anterior, function ($query) use ($anterior) {
                    return $query->where('id', '!=', $anterior->id);
                })
                ->where('inicio', '<', $fim)
                ->where('fim', '>', $inicio)
                ->exists();

            if ($ocupado) {
                return response()->json(['message' => 'Esse horário acabou de ser ocupado.'], 409);
            }

            $agendamento = $paciente->agendamentos()->create([
                'tenant_id' => $paciente->tenant_id,
                'profissional_id' => $profissional->id,
                'servico_id' => $servico->id,
                'inicio' => $inicio,
                'fim' => $fim,
                'status' => Agendamento::AGENDADO,
                'convenio' => $dados['convenio'] ?? null,
            ]);

            if ($anterior) {
                $anterior->alterarStatus(Agendamento::CANCELADO);
            }

            return (new AgendamentoResource($agendamento->load(['profissional', 'servico'])))
                ->response()
                ->setStatusCode(201);
        });
    }

    public function destroy(Request $request, $id)
    {
        $agendamento = $request->user()->agendamentos()->findOrFail($id);

        $agendamento->alterarStatus(Agendamento::CANCELADO);

        return response()->noContent();
    }
}
