<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Bloqueio extends Model
{
    use BelongsToTenant;

    protected $table = 'bloqueios';

    protected $fillable = [
        'tenant_id',
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
