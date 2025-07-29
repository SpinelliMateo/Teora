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
        Schema::create('control_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modelo_id')->constrained('modelos')->onDelete('cascade');
            $table->string('n_serie');
            $table->dateTime('fecha_prearmado')->nullable();
            $table->dateTime('fecha_inyectado')->nullable();
            $table->dateTime('fecha_armado')->nullable();
            $table->dateTime('fecha_embalado')->nullable();
            $table->dateTime('fecha_salida')->nullable();
            $table->boolean('oculto')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('control_stock');
    }
};
