<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('orden_fabricacion_operarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_fabricacion_id')->constrained('ordenes_fabricacion')->onDelete('cascade');
            $table->foreignId('operario_id')->constrained('operarios')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['orden_fabricacion_id', 'operario_id'], 'of_operario_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_fabricacion_operarios');
    }
};
