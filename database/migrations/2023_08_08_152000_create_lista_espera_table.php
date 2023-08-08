<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lista_espera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->foreignId('paciente_id')->constrained('pacientes');
            $table->foreignId('profissional_id')->nullable()->constrained('profissionais');
            $table->foreignId('servico_id')->nullable()->constrained('servicos');
            $table->date('data_desejada');
            $table->string('observacao')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'data_desejada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lista_espera');
    }
};
