<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnexosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('anexos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('agendamento_id');
            $table->string('caminho');
            $table->string('nome_original');
            $table->string('tipo')->nullable();
            $table->unsignedInteger('tamanho')->default(0);
            $table->timestamps();

            $table->foreign('agendamento_id')->references('id')->on('agendamentos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('anexos');
    }
}
