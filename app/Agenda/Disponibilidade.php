<?php

namespace App\Agenda;

use App\Models\Agendamento;
use App\Models\Bloqueio;
use App\Models\Profissional;
use App\Models\Servico;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class Disponibilidade
{
    /**
     * @return array<int, CarbonImmutable>
     */
    public function horariosLivres(Profissional $profissional, Servico $servico, CarbonInterface $data, ?CarbonInterface $agora = null, ?int $ignorarId = null): array
    {
        $data = CarbonImmutable::parse($data->toDateString());

        if ($this->diaBloqueado($profissional, $data)) {
            return [];
        }

        $faixas = $profissional->disponibilidades()
            ->where('dia_semana', $data->dayOfWeek)
            ->orderBy('hora_inicio')
            ->get();

        $ocupados = Agendamento::query()
            ->where('profissional_id', $profissional->id)
            ->where('status', '!=', Agendamento::CANCELADO)
            ->when($ignorarId, fn ($query) => $query->where('id', '!=', $ignorarId))
            ->whereDate('inicio', $data->toDateString())
            ->get(['inicio', 'fim']);

        $horarios = [];

        foreach ($faixas as $faixa) {
            $inicio = CarbonImmutable::parse($data->toDateString().' '.$faixa->hora_inicio);
            $limite = CarbonImmutable::parse($data->toDateString().' '.$faixa->hora_fim);

            while ($inicio->addMinutes($servico->duracao_minutos)->lte($limite)) {
                $fim = $inicio->addMinutes($servico->duracao_minutos);

                $livre = $ocupados->doesntContain(
                    fn (Agendamento $agendamento) => $agendamento->inicio->lt($fim) && $agendamento->fim->gt($inicio)
                );

                if ($livre && ($agora === null || $inicio->toDateTimeString() > $agora->toDateTimeString())) {
                    $horarios[] = $inicio;
                }

                $inicio = $fim;
            }
        }

        return $horarios;
    }

    public function conflita(int $profissionalId, CarbonInterface $inicio, CarbonInterface $fim, ?int $ignorarId = null): bool
    {
        return Agendamento::query()
            ->where('profissional_id', $profissionalId)
            ->where('status', '!=', Agendamento::CANCELADO)
            ->when($ignorarId, fn ($query) => $query->where('id', '!=', $ignorarId))
            ->where('inicio', '<', $fim->toDateTimeString())
            ->where('fim', '>', $inicio->toDateTimeString())
            ->exists();
    }

    public function diaBloqueado(Profissional $profissional, CarbonInterface $data): bool
    {
        if (in_array($data->format('m-d'), config('feriados.fixos', []), true)
            || in_array($data->toDateString(), config('feriados.moveis', []), true)) {
            return true;
        }

        return Bloqueio::query()
            ->where(fn ($query) => $query->whereNull('profissional_id')->orWhere('profissional_id', $profissional->id))
            ->whereDate('data', '<=', $data->toDateString())
            ->where(function ($query) use ($data) {
                $query->whereDate('data_fim', '>=', $data->toDateString())
                    ->orWhere(fn ($query) => $query->whereNull('data_fim')->whereDate('data', $data->toDateString()));
            })
            ->exists();
    }
}
