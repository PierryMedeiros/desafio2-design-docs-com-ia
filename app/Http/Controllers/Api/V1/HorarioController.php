<?php
namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Carbon\CarbonImmutable;
use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\Profissional;
use App\Models\Servico;

class HorarioController extends Controller
{
    public function index(Request $request)
    {
        $dados = $request->validate([
            'profissional_id' => 'required|integer',
            'servico_id' => 'required|integer',
            'data' => 'required|date_format:Y-m-d',
        ]);

        $profissional = Profissional::where('ativo', true)->findOrFail($dados['profissional_id']);
        $servico = Servico::findOrFail($dados['servico_id']);
        $data = CarbonImmutable::createFromFormat('Y-m-d', $dados['data'])->startOfDay();

        $faixas = $profissional->disponibilidades()
            ->where('dia_semana', $data->dayOfWeek)
            ->orderBy('hora_inicio')
            ->get();

        $ocupados = Agendamento::where('profissional_id', $profissional->id)
            ->whereDate('inicio', $data->toDateString())
            ->get(['inicio', 'fim']);

        $horarios = [];

        foreach ($faixas as $faixa) {
            $inicio = CarbonImmutable::parse($data->toDateString().' '.$faixa->hora_inicio);
            $limite = CarbonImmutable::parse($data->toDateString().' '.$faixa->hora_fim);

            while ($inicio->addMinutes($servico->duracao_minutos)->lte($limite)) {
                $fim = $inicio->addMinutes($servico->duracao_minutos);

                $livre = $ocupados->filter(function ($agendamento) use ($inicio, $fim) {
                    return $agendamento->inicio->lt($fim) && $agendamento->fim->gt($inicio);
                })->isEmpty();

                if ($livre) {
                    $horarios[] = $inicio->format('H:i');
                }

                $inicio = $fim;
            }
        }

        return $this->resposta($data, $profissional, $servico, $horarios);
    }

    private function resposta(CarbonImmutable $data, Profissional $profissional, Servico $servico, array $horarios)
    {
        return response()->json([
            'data' => $data->toDateString(),
            'profissional_id' => $profissional->id,
            'servico_id' => $servico->id,
            'horarios' => $horarios,
        ]);
    }
}
