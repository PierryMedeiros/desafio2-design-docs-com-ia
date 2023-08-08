<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListaEspera extends Model
{
    use BelongsToTenant;

    protected $table = 'lista_espera';

    protected $fillable = [
        'tenant_id',
        'paciente_id',
        'profissional_id',
        'servico_id',
        'data_desejada',
        'observacao',
    ];

    protected $casts = [
        'data_desejada' => 'date',
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
}
