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
        Schema::table('info_equipos', function (Blueprint $table) {
            $table->string('marca');
            $table->string('modelo');
            $table->string('registro_sanitario')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('info_equipos', function (Blueprint $table) {
            $table->dropColumn('marca');
            $table->dropColumn('modelo');
            $table->dropColumn('registro_sanitario');
        });
    }
};
