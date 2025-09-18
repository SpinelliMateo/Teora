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
        Schema::create('actividades_dashboard', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // ID del usuario/administrador
            $table->string('descripcion'); // Descripción de la actividad
            $table->enum('tipo', [
                'carga', 
                'modificacion', 
                'pausa', 
                'reanudacion', 
                'finalizacion', 
                'eliminacion',
                'creacion',
                'actualizacion'
            ]); // Tipo de actividad
            $table->string('modulo')->nullable(); // Módulo donde se realizó la acción (ordenes, usuarios, etc.)
            $table->unsignedBigInteger('referencia_id')->nullable(); // ID del registro afectado
            $table->string('referencia_tipo')->nullable(); // Tipo del registro afectado (orden, usuario, etc.)
            $table->json('datos_adicionales')->nullable(); // Datos adicionales en JSON
            $table->timestamps();
            
            // Índices
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['created_at', 'user_id']);
            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actividades_dashboard');
    }
};