<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historial de auditoría y trazabilidad de estados de cada cita.
     * Registra: creación, ediciones, cancelaciones y paso a 'Realizada'
     * con usuario, estados anterior/nuevo y detalle (motivo).
     */
    public function up()
    {
        Schema::create('cita_auditorias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_cita');
            $table->string('accion', 20); // creada | editada | cancelada | realizada
            $table->string('estado_anterior', 20)->nullable();
            $table->string('estado_nuevo', 20)->nullable();
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->string('usuario', 150)->nullable();
            $table->text('detalle')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('id_cita')
                  ->references('id')
                  ->on('citas')
                  ->onDelete('cascade');

            $table->foreign('id_usuario')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->index(['id_cita', 'created_at']);
            $table->index('accion');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cita_auditorias');
    }
};
