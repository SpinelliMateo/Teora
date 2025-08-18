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
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_alerta');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('serie');
            $table->foreignId('modelo_id')->constrained('modelos')->onDelete('cascade');
            $table->string('motivo');
            $table->boolean('resuelto')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
