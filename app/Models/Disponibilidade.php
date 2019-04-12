<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disponibilidade extends Model
{
    protected $table = 'disponibilidades';

    protected $fillable = [
        'profissional_id',
        'dia_semana',
        'hora_inicio',
        'hora_fim',
    ];

    protected $casts = [
        'dia_semana' => 'integer',
    ];

    public function profissional()
    {
        return $this->belongsTo(Profissional::class);
    }
}
