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
        Schema::table('operarios', function (Blueprint $table) {
            // Agregar las nuevas columnas
            $table->string('n_legajo')->nullable()->after('apellido');
            $table->text('codigo_qr')->nullable()->after('n_legajo');
            
            // Eliminar la columna rol
            $table->dropColumn('rol');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operarios', function (Blueprint $table) {
            // Restaurar la columna rol
            $table->string('rol')->default('operario')->after('apellido');
            
            // Eliminar las columnas agregadas
            $table->dropColumn(['n_legajo', 'codigo_qr']);
        });
    }
};