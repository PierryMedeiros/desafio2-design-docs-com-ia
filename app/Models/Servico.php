<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Servico extends Model
{
    protected $table = 'servicos';

    protected $fillable = [
        'nome',
        'duracao_minutos',
    ];

    protected $casts = [
        'duracao_minutos' => 'integer',
    ];
}
