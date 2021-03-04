<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Paciente extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'pacientes';

    protected $fillable = [
        'nome',
        'cpf',
        'telefone',
        'email',
        'data_nascimento',
        'senha',
    ];

    protected $hidden = [
        'senha',
        'remember_token',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
    ];

    public function agendamentos()
    {
        return $this->hasMany(Agendamento::class);
    }

    public function getAuthPassword()
    {
        return $this->senha;
    }
}
