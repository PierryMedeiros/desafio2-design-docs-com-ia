<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bloqueio extends Model
{
    protected $table = 'bloqueios';

    protected $fillable = [
        'profissional_id',
        'data',
        'data_fim',
        'motivo',
    ];

    protected $casts = [
        'data' => 'date',
        'data_fim' => 'date',
    ];

    public function profissional()
    {
        return $this->belongsTo(Profissional::class);
    }
}
