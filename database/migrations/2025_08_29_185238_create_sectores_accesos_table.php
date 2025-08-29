<?php
// database/migrations/xxxx_xx_xx_create_sector_accesos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sector_accesos', function (Blueprint $table) {
            $table->id();
            $table->enum('sector', ['prearmado', 'inyectado', 'armado', 'embalado', 'despacho'])->unique();
            $table->string('codigo_hash');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Insertar códigos por defecto (cambiarlos después desde admin)
        DB::table('sector_accesos')->insert([
            ['sector' => 'prearmado', 'codigo_hash' => Hash::make('PREARM001'), 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['sector' => 'inyectado', 'codigo_hash' => Hash::make('INYECT001'), 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['sector' => 'armado', 'codigo_hash' => Hash::make('ARMADO001'), 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['sector' => 'embalado', 'codigo_hash' => Hash::make('EMBAL001'), 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['sector' => 'despacho', 'codigo_hash' => Hash::make('DESP001'), 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sector_accesos');
    }
};