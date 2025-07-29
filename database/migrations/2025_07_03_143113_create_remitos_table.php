<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('remitos', function (Blueprint $table) {
            $table->id();
            $table->string('n_remito', 8)->unique();
            $table->string('cliente');
            $table->enum('estado', ['procesado', 'despachado', 'finalizado'])->default('procesado');
            $table->timestamps();
        });
        Schema::create('remitos_modelos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remito_id')->constrained('remitos')->onDelete('cascade');
            $table->foreignId('modelo_id')->constrained('modelos')->onDelete('cascade');
            $table->integer('cantidad')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remitos');
        Schema::dropIfExists('remitos_modelos');
    }
};
