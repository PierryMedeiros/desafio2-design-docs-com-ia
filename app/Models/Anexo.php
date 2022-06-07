<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Tenancy\BelongsToTenant;

class Anexo extends Model
{
    use BelongsToTenant;

    protected $table = 'anexos';

    protected $fillable = [
        'tenant_id',
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
