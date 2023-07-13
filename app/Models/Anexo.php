<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Anexo extends Model
{
    use BelongsToTenant;

    protected $table = 'anexos';

    protected $fillable = [
        'tenant_id',
        'agendamento_id',
        'caminho',
        'nome_original',
        'tipo',
        'tamanho',
    ];

    protected $casts = [
        'tamanho' => 'integer',
    ];

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class);
    }
}
