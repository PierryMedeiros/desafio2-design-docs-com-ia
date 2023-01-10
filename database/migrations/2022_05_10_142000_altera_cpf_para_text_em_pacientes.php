<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE pacientes ALTER COLUMN cpf TYPE text');
    }

    public function down()
    {
        DB::statement('ALTER TABLE pacientes ALTER COLUMN cpf TYPE varchar(14)');
    }
};
