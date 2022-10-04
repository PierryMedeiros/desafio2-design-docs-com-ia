<?php
namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Agendamento;

class AgendamentoStatusAlterado
{
    use Dispatchable, SerializesModels;

    public function __construct(public Agendamento $agendamento, public ?string $statusAnterior)

    {

    }
}
