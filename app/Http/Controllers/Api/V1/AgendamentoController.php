<?php

namespace App\Http\Controllers\Api\V1;

use App\Agenda\Disponibilidade;
use App\Http\Controllers\Controller;
use App\Http\Resources\AgendamentoResource;
use App\Models\Agendamento;
use App\Models\Profissional;
use App\Models\Servico;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AgendamentoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $agendamentos = $request->user()
            ->agendamentos()
            ->with(['profissional', 'servico'])
            ->orderByDesc('inicio')
            ->paginate(20);

        return AgendamentoResource::collection($agendamentos);
    }

    public function store(Request $request, Disponibilidade $disponibilidade): JsonResponse
    {
        $paciente = $request->user();

        $dados = $request->validate([
            'profissional_id' => ['required', 'integer'],
            'servico_id' => ['required', 'integer'],
            'inicio' => ['required', 'date_format:Y-m-d H:i'],
            'convenio' => ['nullable', 'string', 'max:100'],
            'reagendar_de' => ['nullable', 'integer'],
        ]);

        $anterior = isset($dados['reagendar_de'])
            ? $paciente->agendamentos()->whereIn('status', [Agendamento::AGENDADO, Agendamento::CONFIRMADO])->findOrFail($dados['reagendar_de'])
            : null;

        $servico = Servico::findOrFail($dados['servico_id']);
        $inicio = CarbonImmutable::createFromFormat('Y-m-d H:i', $dados['inicio']);
        $fim = $inicio->addMinutes($servico->duracao_minutos);

        return DB::transaction(function () use ($dados, $paciente, $servico, $inicio, $fim, $anterior, $disponibilidade) {
            $profissional = Profissional::where('ativo', true)->lockForUpdate()->findOrFail($dados['profissional_id']);

            if ($disponibilidade->conflita($profissional->id, $inicio, $fim, $anterior?->id)) {
                return response()->json(['message' => 'Esse horário acabou de ser ocupado.'], 409);
            }

            $livres = $disponibilidade->horariosLivres($profissional, $servico, $inicio, $this->tenant()->agora(), $anterior?->id);
            $livres = array_map(fn (CarbonImmutable $horario) => $horario->format('H:i'), $livres);

            if (! in_array($inicio->format('H:i'), $livres, true)) {
                return response()->json(['message' => 'Horário fora da agenda do profissional.'], 422);
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

            $anterior?->alterarStatus(Agendamento::CANCELADO);

            return (new AgendamentoResource($agendamento->load(['profissional', 'servico'])))
                ->response()
                ->setStatusCode(201);
        });
    }

    public function destroy(Request $request, int $id): Response
    {
        $agendamento = $request->user()->agendamentos()->findOrFail($id);

        $agendamento->alterarStatus(Agendamento::CANCELADO);

        return response()->noContent();
    }
}
