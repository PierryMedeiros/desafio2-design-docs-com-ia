<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Servico extends Model
{
    use BelongsToTenant;

    protected $table = 'servicos';

    protected $fillable = [
        'tenant_id',
        'nome',
        'duracao_minutos',
    ];

    protected $casts = [
        'duracao_minutos' => 'integer',
    ];
}
