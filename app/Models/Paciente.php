<?php

namespace App\Models;

use App\Criptografia\HashCpf;
use App\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Paciente extends Authenticatable
{
    use BelongsToTenant, HasApiTokens;

    protected $table = 'pacientes';

    protected $fillable = [
        'tenant_id',
        'nome',
        'cpf',
        'telefone',
        'email',
        'data_nascimento',
        'senha',
        'aceita_whatsapp',
    ];

    protected $hidden = [
        'cpf',
        'cpf_hash',
        'senha',
        'remember_token',
    ];

    protected $casts = [
        'cpf' => 'encrypted',
        'data_nascimento' => 'date',
        'senha' => 'hashed',
        'aceita_whatsapp' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Paciente $paciente) {
            if ($paciente->isDirty('cpf')) {
                $paciente->cpf_hash = HashCpf::gerar($paciente->cpf);
            }
        });
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    public function getAuthPassword()
    {
        return $this->senha;
    }

    public function cpfFormatado(): ?string
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
