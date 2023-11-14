<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lista_espera', function (Blueprint $table) {
            $table->timestamp('avisado_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lista_espera', function (Blueprint $table) {
            $table->dropColumn('avisado_em');
        });
    }
};
