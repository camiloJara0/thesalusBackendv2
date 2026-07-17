<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kardex_registros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_paciente')->constrained('pacientes')->onDelete('cascade');
            $table->foreignId('id_campo')->constrained('kardex_campos')->onDelete('cascade');
            $table->text('valor')->nullable();
            $table->unique(['id_paciente', 'id_campo']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kardex_registros');
    }
};
