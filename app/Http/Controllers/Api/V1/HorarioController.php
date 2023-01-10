<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\Bloqueio;
use App\Models\Profissional;
use App\Models\Servico;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

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
        $agora = CarbonImmutable::now($this->tenant()->timezone);

        if ($this->diaBloqueado($profissional, $data)) {
            return $this->resposta($data, $profissional, $servico, []);
        }

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

                if ($livre && $inicio->toDateTimeString() > $agora->toDateTimeString()) {
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

    private function diaBloqueado(Profissional $profissional, CarbonImmutable $data)
    {
        if (in_array($data->format('m-d'), config('feriados.fixos')) || in_array($data->toDateString(), config('feriados.moveis'))) {
            return true;
        }

        return Bloqueio::query()
            ->where(function ($query) use ($profissional) {
                $query->whereNull('profissional_id')->orWhere('profissional_id', $profissional->id);
            })
            ->whereDate('data', '<=', $data->toDateString())
            ->where(function ($query) use ($data) {
                $query->whereDate('data_fim', '>=', $data->toDateString())
                    ->orWhere(function ($query) use ($data) {
                        $query->whereNull('data_fim')->whereDate('data', $data->toDateString());
                    });
            })
            ->exists();
    }
}
