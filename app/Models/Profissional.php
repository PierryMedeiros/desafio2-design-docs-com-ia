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
}
