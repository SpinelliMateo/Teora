<?php
// app/Models/SectorAcceso.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class SectorAcceso extends Model
{
    protected $table = 'sector_accesos';
    
    protected $fillable = [
        'sector',
        'codigo_hash', 
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Sectores disponibles
    public static function getSectoresDisponibles(): array
    {
        return [
            'prearmado' => 'Prearmado',
            'inyectado' => 'Inyectado', 
            'armado' => 'Armado',
            'embalado' => 'Embalado',
            'despacho' => 'Despacho'
        ];
    }

    // Verificar código
    public function verificarCodigo(string $codigo): bool
    {
        return $this->activo && Hash::check($codigo, $this->codigo_hash);
    }

    // Actualizar código
    public function actualizarCodigo(string $nuevoCodigo): bool
    {
        return $this->update([
            'codigo_hash' => Hash::make($nuevoCodigo)
        ]);
    }

    // Obtener por sector
    public static function obtenerPorSector(string $sector): ?self
    {
        return self::where('sector', $sector)->first();
    }
}