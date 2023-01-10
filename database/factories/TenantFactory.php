<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition()
    {
        $nome = 'Clínica '.$this->faker->unique()->lastName();

        return [
            'nome' => $nome,
            'slug' => Str::slug($nome),
            'timezone' => 'America/Sao_Paulo',
        ];
    }
}
