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
        DB::statement("ALTER TABLE kardex_campos MODIFY COLUMN tipo ENUM('text', 'boolean', 'select', 'date', 'number', 'textarea', 'suma', 'resta', 'multiplicacion', 'division') NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE kardex_campos MODIFY COLUMN tipo ENUM('text', 'boolean', 'select', 'date', 'number', 'textarea') NOT NULL");
    }
};
