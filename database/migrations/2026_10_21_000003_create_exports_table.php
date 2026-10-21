<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('servicio');
            $table->json('filtros')->nullable();
            $table->string('estado')->default('pendiente')->index();
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('completados')->default(0);
            $table->string('zip_path')->nullable();
            $table->string('zip_name')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('exports');
    }
};
