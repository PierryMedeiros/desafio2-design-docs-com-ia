<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Tenancy\BelongsToTenant;

class Disponibilidade extends Model
{
    use BelongsToTenant;

    protected $table = 'disponibilidades';

    protected $fillable = [
        'tenant_id',
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
