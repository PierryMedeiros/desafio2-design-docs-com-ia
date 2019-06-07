<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Anexo extends Model
{
    protected $table = 'anexos';

    protected $fillable = [
        'agendamento_id',
        'caminho',
        'nome_original',
        'tipo',
        'tamanho',
    ];

    protected $casts = [
        'tamanho' => 'integer',
    ];

    public function agendamento()
    {
        return $this->belongsTo(Agendamento::class);
    }
}
