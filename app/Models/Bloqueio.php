<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Tenancy\BelongsToTenant;

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
