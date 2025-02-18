<?php

namespace App\Http\Controllers\Api\V1;

use App\Agenda\Disponibilidade;
use App\Http\Controllers\Controller;
use App\Models\Profissional;
use App\Models\Servico;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HorarioController extends Controller
{
    public function index(Request $request, Disponibilidade $disponibilidade): JsonResponse
    {
        $agora = $this->tenant()->agora();

        $dados = $request->validate([
            'profissional_id' => ['required', 'integer'],
            'servico_id' => ['required', 'integer'],
            'data' => ['required', 'date_format:Y-m-d'],
        ]);

        $profissional = Profissional::where('ativo', true)->findOrFail($dados['profissional_id']);
        $servico = Servico::findOrFail($dados['servico_id']);
        $data = CarbonImmutable::createFromFormat('Y-m-d', $dados['data']);

        $horarios = $disponibilidade->horariosLivres($profissional, $servico, $data, $agora);

        return response()->json([
            'data' => $data->toDateString(),
            'profissional_id' => $profissional->id,
            'servico_id' => $servico->id,
            'horarios' => array_map(fn (CarbonImmutable $horario) => $horario->format('H:i'), $horarios),
        ]);
    }
}
