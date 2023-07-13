<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }
}
