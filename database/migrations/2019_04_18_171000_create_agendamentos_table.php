<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgendamentosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agendamentos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('paciente_id');
            $table->unsignedBigInteger('profissional_id');
            $table->unsignedBigInteger('servico_id');
            $table->dateTime('inicio');
            $table->dateTime('fim');
            $table->string('status')->default('agendado');
            $table->text('notas_clinicas')->nullable();
            $table->timestamps();

            $table->foreign('paciente_id')->references('id')->on('pacientes');
            $table->foreign('profissional_id')->references('id')->on('profissionais');
            $table->foreign('servico_id')->references('id')->on('servicos');
            $table->index(['profissional_id', 'inicio']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agendamentos');
    }
}
