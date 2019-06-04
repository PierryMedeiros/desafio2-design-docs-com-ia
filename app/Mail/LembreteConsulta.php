<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Agendamento;
use App\Models\Tenant;

class LembreteConsulta extends Mailable
{
    use Queueable, SerializesModels;

    public $agendamento;

    public $tenant;

    public function __construct(Agendamento $agendamento, Tenant $tenant)
    {
        $this->agendamento = $agendamento;
        $this->tenant = $tenant;
    }

    public function build()
    {
        return $this->subject('Lembrete da sua consulta na '.$this->tenant->nome)
            ->view('emails.lembrete');
    }
}
