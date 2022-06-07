<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Tenancy\BelongsToTenant;

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

    public function disponibilidades()
    {
        return $this->hasMany(Disponibilidade::class);
    }

    public function agendamentos()
    {
        return $this->hasMany(Agendamento::class);
    }

    public function bloqueios()
    {
        return $this->hasMany(Bloqueio::class);
    }
}
