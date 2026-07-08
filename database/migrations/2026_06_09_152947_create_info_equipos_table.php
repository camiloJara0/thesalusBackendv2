<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('info_equipos', function (Blueprint $table) {
            $table->id();
            $table->string('serial');
            $table->foreignId('tipo_equipo_id')->constrained('tipo_equipos');
            $table->foreignId('inventario_id')->constrained('insumos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('info_equipos');
    }
};
