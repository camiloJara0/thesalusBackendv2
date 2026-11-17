<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metricas_api_dias', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->unsignedInteger('solicitudes')->default(0);
            $table->unsignedInteger('errores_4xx')->default(0);
            $table->unsignedInteger('errores_5xx')->default(0);
            $table->unsignedInteger('latencia_promedio_ms')->default(0);
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metricas_api_dias');
    }
};
