<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use BelongsToTenant, HasFactory, Notifiable;

    const PAPEL_ADMIN = 'admin';

    const PAPEL_RECEPCAO = 'recepcao';

    const PAPEL_PROFISSIONAL = 'profissional';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'papel',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
