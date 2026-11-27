<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Código de acceso temporal (asistencia a usuarios).
 *
 * Reutiliza la tabla codigo_verificaciones (ya existente) en lugar de crear
 * una tabla nueva. Las columnas nuevas son nulas para no afectar los códigos
 * de recuperación por correo ya registrados.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('codigo_verificaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('generado_por')->nullable()->after('usado')->index();
            $table->timestamp('usado_en')->nullable()->after('generado_por');
            $table->string('ip_uso', 45)->nullable()->after('usado_en');
        });
    }

    public function down()
    {
        Schema::table('codigo_verificaciones', function (Blueprint $table) {
            $table->dropIndex(['generado_por']);
            $table->dropColumn(['generado_por', 'usado_en', 'ip_uso']);
        });
    }
};
