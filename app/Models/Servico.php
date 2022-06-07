<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Tenancy\BelongsToTenant;

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
