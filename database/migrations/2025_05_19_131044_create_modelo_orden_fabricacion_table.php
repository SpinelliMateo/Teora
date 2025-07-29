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
        Schema::create('modelo_orden_fabricacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_fabricacion_id')->constrained('ordenes_fabricacion')->onDelete('cascade');
            $table->foreignId('modelo_id')->constrained('modelos')->onDelete('cascade');
            $table->integer('cantidad'); 
            $table->timestamps(); 

            $table->unique(['modelo_id', 'orden_fabricacion_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modelo_orden_fabricacion');
    }
};
