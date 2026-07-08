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
        Schema::table('insumos', function (Blueprint $table) {
            $table->dropColumn('activo');
            $table->dropColumn('receta');
            $table->dropColumn('unidad');
            $table->dropColumn('lote');
            $table->dropColumn('vencimiento');
            $table->dropColumn('ubicacion');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('insumos', function (Blueprint $table) {
            $table->string('activo');
            $table->string('receta');
            $table->string('unidad');
            $table->string('lote');
            $table->date('vencimiento');
            $table->string('ubicacion');
        });
    }
};
