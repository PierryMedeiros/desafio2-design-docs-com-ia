<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Tenancy\BelongsToTenant;

class Agendamento extends Model
{
    use BelongsToTenant;

    const AGENDADO = 'agendado';

    const CONFIRMADO = 'confirmado';

    const CANCELADO = 'cancelado';

    const FALTOU = 'faltou';

    const REALIZADO = 'realizado';

    const STATUS = [
        self::AGENDADO,
        self::CONFIRMADO,
        self::CANCELADO,
        self::FALTOU,
        self::REALIZADO,
    ];

    protected $table = 'agendamentos';

    protected $fillable = [
        'tenant_id',
        'paciente_id',
        'profissional_id',
        'servico_id',
        'inicio',
        'fim',
        'status',
        'link_teleconsulta',
        'convenio',
        'notas_clinicas',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fim' => 'datetime',
        'lembrete_enviado_em' => 'datetime',
        'notas_clinicas' => 'encrypted',
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

    public function anexos()
    {
        return $this->hasMany(Anexo::class);
    }
}
