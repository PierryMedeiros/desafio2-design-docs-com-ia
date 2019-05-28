<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBloqueiosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bloqueios', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('profissional_id')->nullable();
            $table->date('data');
            $table->string('motivo')->nullable();
            $table->timestamps();

            $table->foreign('profissional_id')->references('id')->on('profissionais')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bloqueios');
    }
}
