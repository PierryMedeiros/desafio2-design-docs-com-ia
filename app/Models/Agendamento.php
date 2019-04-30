<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agendamento extends Model
{
    const AGENDADO = 'agendado';

    const CONFIRMADO = 'confirmado';

    const CANCELADO = 'cancelado';

    const STATUS = [
        self::AGENDADO,
        self::CONFIRMADO,
        self::CANCELADO,
    ];

    protected $table = 'agendamentos';

    protected $fillable = [
        'paciente_id',
        'profissional_id',
        'servico_id',
        'inicio',
        'fim',
        'status',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fim' => 'datetime',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function profissional()
    {
        return $this->belongsTo(Profissional::class);
    }

    public function servico()
    {
        return $this->belongsTo(Servico::class);
    }
}
