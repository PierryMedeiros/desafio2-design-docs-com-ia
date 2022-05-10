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
        'cpf',
        'senha',
        'remember_token',
    ];

    protected $casts = [
        'cpf' => 'encrypted',
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

    public function cpfFormatado()
    {
        $digitos = preg_replace('/\D/', '', (string) $this->cpf);

        if (strlen($digitos) !== 11) {
            return $this->cpf;
        }

        return vsprintf('%s.%s.%s-%s', [
            substr($digitos, 0, 3),
            substr($digitos, 3, 3),
            substr($digitos, 6, 3),
            substr($digitos, 9, 2),
        ]);
    }
}
