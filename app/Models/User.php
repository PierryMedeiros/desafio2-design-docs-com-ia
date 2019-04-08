<?php
namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    const PAPEL_ADMIN = 'admin';

    const PAPEL_RECEPCAO = 'recepcao';

    const PAPEL_PROFISSIONAL = 'profissional';

    protected $fillable = [
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
