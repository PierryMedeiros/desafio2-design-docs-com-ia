<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'nome',
        'slug',
        'schema',
        'timezone',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
