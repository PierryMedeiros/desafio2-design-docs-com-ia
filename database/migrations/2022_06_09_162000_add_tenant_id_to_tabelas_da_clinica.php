<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private $tabelas = [
        'profissionais',
        'servicos',
        'disponibilidades',
        'bloqueios',
        'pacientes',
        'agendamentos',
        'anexos',
    ];

    public function up()
    {
        foreach ($this->tabelas as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants');
                $table->index('tenant_id');
            });
        }

        Schema::table('pacientes', function (Blueprint $table) {
            $table->unique(['tenant_id', 'email']);
        });
    }

    public function down()
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'email']);
        });

        foreach ($this->tabelas as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_id');
            });
        }
    }
};
