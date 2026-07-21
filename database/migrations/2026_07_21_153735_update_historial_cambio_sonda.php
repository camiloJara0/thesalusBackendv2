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
        Schema::table('historial_cambio_sonda', function (Blueprint $table) {
            $table->dropForeign(['id_kardex']); // elimina FK
        });

        Schema::table('historial_cambio_sonda', function (Blueprint $table) {
            $table->dropColumn('id_kardex');
            $table->unsignedBigInteger('id_paciente')->after('id');
        });
    }

    /**
     * Reverse the migrations.
    *
    * @return void
    */
    
    public function down()
    {
        Schema::table('historial_cambio_sonda', function (Blueprint $table) {
            $table->dropColumn('id_paciente');
            $table->unsignedBigInteger('id_kardex')->after('id');
        });
    
        // Volver a crear la FK si existía
        Schema::table('historial_cambio_sonda', function (Blueprint $table) {
            $table->foreign('id_kardex')
                  ->references('id')
                  ->on('kardex');
        });
    }
};
