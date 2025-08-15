<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_fabricacion', function (Blueprint $table) {
            $table->enum('estado', ['pendiente', 'en_proceso', 'completada'])
            ->default('pendiente'); 
        });
    }
    
    public function down(): void
    {
        Schema::table('orden_fabricacion', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }

};
