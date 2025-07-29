<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Operario;
use Illuminate\Support\Facades\DB;

class OperariosSeeder extends Seeder
{
    public function run(): void
    {
        $prearmadores = DB::table('prearmadores')->get();
        foreach ($prearmadores as $prearmador) {
            Operario::updateOrCreate(['nombre' => $prearmador->nombre], [
                'rol' => 'prearmador',
                'activo' => true,
            ]);
        }

        $armadores = DB::table('armadores')->get();
        foreach ($armadores as $armador) {
            Operario::updateOrCreate(['nombre' => $armador->nombre], [
                'rol' => 'armador',
                'activo' => true,
            ]);
        }

        $embaladores = DB::table('embaladores')->get();
        foreach ($embaladores as $embalador) {
            Operario::updateOrCreate(['nombre' => $embalador->nombre], [
                'nombre' => $embalador->nombre,
                'rol' => 'embalador',
                'activo' => true,
            ]);
        }

        // $operarios = [
        //     ['nombre' => 'Adrian', 'apellido' => 'Gonzales'],
        //     ['nombre' => 'Roberto', 'apellido' => 'Perez'],
        //     ['nombre' => 'Manuel', 'apellido' => 'Fernandez'],
        //     ['nombre' => 'Carlos', 'apellido' => 'Martinez'],
        //     ['nombre' => 'María', 'apellido' => 'Rodriguez'],
        //     ['nombre' => 'José', 'apellido' => 'Lopez'],
        //     ['nombre' => 'Ana', 'apellido' => 'Garcia'],
        // ];

        // foreach ($operarios as $operario) {
        //     Operario::create([
        //         'nombre' => $operario['nombre'],
        //         'apellido' => $operario['apellido'],
        //         'activo' => true,
        //     ]);
        // }
    }
}