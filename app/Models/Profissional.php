<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profissional extends Model
{
    use BelongsToTenant;

    protected $table = 'profissionais';

    protected $fillable = [
        'tenant_id',
        'nome',
        'especialidade',
        'registro',
        'email',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function disponibilidades(): HasMany
    {
        return $this->hasMany(Disponibilidade::class);
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    public function bloqueios(): HasMany
    {
        return $this->hasMany(Bloqueio::class);
    }
}
