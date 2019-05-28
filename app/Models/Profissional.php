<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profissional extends Model
{
    protected $table = 'profissionais';

    protected $fillable = [
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
