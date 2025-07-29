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
        Schema::create('modelos', function (Blueprint $table) {
            $table->id(); 
            $table->string('modelo', 5)->unique();
            $table->string('nombre_modelo', 20);
            $table->string('tension', 10);
            $table->string('frecuencia', 10);
            $table->string('corriente', 10);
            $table->string('potencia', 20)->nullable();
            $table->string('aislacion', 10);
            $table->string('sistema', 10);
            $table->string('volumen', 10);
            $table->string('espumante', 10);
            $table->string('clase', 10);
            $table->string('gas', 15)->nullable();
            $table->string('cantidad_gas', 15);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modelos');
    }
};
