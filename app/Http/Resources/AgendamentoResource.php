<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AgendamentoResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'inicio' => $this->inicio->format('Y-m-d H:i'),
            'fim' => $this->fim->format('Y-m-d H:i'),
            'status' => $this->status,
            'link_teleconsulta' => $this->link_teleconsulta,
            'convenio' => $this->convenio,
            'profissional' => [
                'id' => $this->profissional->id,
                'nome' => $this->profissional->nome,
                'especialidade' => $this->profissional->especialidade,
            ],
            'servico' => [
                'id' => $this->servico->id,
                'nome' => $this->servico->nome,
                'duracao_minutos' => $this->servico->duracao_minutos,
            ],
        ];
    }
}
