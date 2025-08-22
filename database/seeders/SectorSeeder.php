<?php

// ====================================
// database/seeders/SectorSeeder.php
// ====================================

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Sector;

class SectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sectores = [
            [
                'nombre' => 'Prearmado',
                'descripcion' => 'Sector encargado de la preparación y prearmado de componentes antes del armado final.',
                'activo' => true,
            ],
            [
                'nombre' => 'Armado',
                'descripcion' => 'Sector responsable del ensamblaje y armado final de los productos.',
                'activo' => true,
            ],
            [
                'nombre' => 'Embalado',
                'descripcion' => 'Sector dedicado al empaquetado, etiquetado y preparación final para el envío de productos.',
                'activo' => true,
            ],
        ];

        foreach ($sectores as $sector) {
            Sector::create($sector);
        }
    }
}