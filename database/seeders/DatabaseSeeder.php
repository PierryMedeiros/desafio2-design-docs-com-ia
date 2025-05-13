<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        if (Tenant::query()->exists()) {
            $this->command?->info('Banco já tem clínicas, seed ignorado.');

            return;
        }

        $this->call([
            ClinicaBemEstarSeeder::class,
            FisioMovimentoSeeder::class,
        ]);
    }
}
