<?php

namespace App\Models;

use App\Events\AgendamentoStatusAlterado;
use App\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agendamento extends Model
{
    use BelongsToTenant;

    public const AGENDADO = 'agendado';

    public const CONFIRMADO = 'confirmado';

    public const CANCELADO = 'cancelado';

    public const FALTOU = 'faltou';

    public const REALIZADO = 'realizado';

    public const STATUS = [
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

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(Anexo::class);
    }

    public function alterarStatus(string $status): void
    {
        $anterior = $this->status;

        if ($anterior === $status) {
            return;
        }

        $this->status = $status;
        $this->save();

        AgendamentoStatusAlterado::dispatch($this, $anterior);
    }
}
