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
        Schema::create('ordenes_fabricacion', function (Blueprint $table) {
            $table->id();
            $table->date('fecha'); 
            $table->date('fecha_finalizacion')->nullable();
            $table->string('no_orden')->unique(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes_fabricacion');
    }
};
