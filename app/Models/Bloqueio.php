<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bloqueio extends Model
{
    protected $table = 'bloqueios';

    protected $fillable = [
        'profissional_id',
        'data',
        'motivo',
    ];

    protected $casts = [
        'data' => 'date',
    ];

    public function profissional()
    {
        return $this->belongsTo(Profissional::class);
    }
}
