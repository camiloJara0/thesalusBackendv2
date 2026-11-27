<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repositorio de PDFs por análisis (AWS S3).
     * Migración puramente aditiva: agrega columnas nullable, NO
     * modifica ni elimina datos existentes. Los análisis históricos
     * quedan con pdf_almacenado = NULL y se migran de forma perezosa
     * (generación bajo demanda en su primer uso/exportación).
     */
    public function up()
    {
        Schema::table('analises', function (Blueprint $table) {
            $table->string('pdf_almacenado', 300)->nullable()->after('tratamiento');
            $table->timestamp('pdf_almacenado_at')->nullable()->after('pdf_almacenado');
        });
    }

    public function down()
    {
        Schema::table('analises', function (Blueprint $table) {
            $table->dropColumn(['pdf_almacenado', 'pdf_almacenado_at']);
        });
    }
};
