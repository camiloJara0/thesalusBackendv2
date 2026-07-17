<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kardex_plantilla_campos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_plantilla')->constrained('kardex_plantillas')->onDelete('cascade');
            $table->foreignId('id_campo')->constrained('kardex_campos')->onDelete('cascade');
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('requerido')->default(false);
            $table->unique(['id_plantilla', 'id_campo']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kardex_plantilla_campos');
    }
};
