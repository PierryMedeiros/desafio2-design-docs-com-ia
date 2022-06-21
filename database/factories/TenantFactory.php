<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Tenant;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition()
    {
        $nome = 'Clínica '.$this->faker->unique()->lastName();

        return [
            'nome' => $nome,
            'slug' => Str::slug($nome),
            'schema' => 'clinica_'.Str::snake(Str::slug($nome, '_')),
            'timezone' => 'America/Sao_Paulo',
        ];
    }
}
