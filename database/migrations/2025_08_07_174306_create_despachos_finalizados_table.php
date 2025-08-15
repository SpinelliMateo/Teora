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
        Schema::create('despachos_finalizados', function (Blueprint $table) {
            $table->id();
            $table->string('numero_despacho')->unique();
            $table->timestamps();
        });
        Schema::create('despachos_remitos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('despacho_id')->constrained('despachos_finalizados')->onDelete('cascade');
            $table->foreignId('remito_id')->constrained('remitos')->onDelete('cascade');
            $table->timestamps();
        });
        Schema::create('controlstock_remitos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remito_id')->constrained('remitos')->onDelete('cascade');
            $table->foreignId('control_stock_id')->constrained('control_stock')->onDelete('cascade');
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('despachos_finalizados');
        Schema::dropIfExists('despachos_remitos');
        Schema::dropIfExists('controlstock_remitos');
    }
};
