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
        Schema::create('procesos_operarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('control_stock_id')->constrained('control_stock')->onDelete('cascade');
            $table->foreignId('operario_armador_id')->nullable()->constrained('operarios')->onDelete('cascade');
            $table->foreignId('operario_prearmador_id')->nullable()->constrained('operarios')->onDelete('cascade');
            $table->foreignId('operario_embalador_id')->nullable()->constrained('operarios')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procesos_operarios');
    }
};
