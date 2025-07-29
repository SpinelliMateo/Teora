<?php

namespace Database\Seeders;

use App\Models\Modelo;
use App\Models\StockMinimo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrarModelosSeeder extends Seeder
{
    public function run(): void
    {
        $modelos = DB::table('etiquetas')->get();
        $modelosAgregados = [];

        foreach ($modelos as $modelo) {
            if (in_array($modelo->nombremodelo, $modelosAgregados)) {
                continue;
            }

            $modelosAgregados[] = $modelo->nombremodelo;

            Modelo::updateOrInsert(
                ['nombre_modelo' => $modelo->nombremodelo],
                [
                    'modelo'       => $modelo->modelo,
                    'tension'      => $modelo->tension,
                    'frecuencia'   => $modelo->frecuencia,
                    'corriente'    => $modelo->corriente,
                    'potencia'     => $modelo->potencia,
                    'aislacion'    => $modelo->aislacion,
                    'sistema'      => $modelo->sistema,
                    'volumen'      => $modelo->volumen,
                    'espumante'    => $modelo->espumante,
                    'clase'        => $modelo->clase,
                    'gas'          => $modelo->gas,
                    'cantidad_gas' => $modelo->cantgas,
                ]
            );

            StockMinimo::updateOrCreate(
                ['modelo_id' => $modelo->modelo],
                [
                    'modelo_id'       => $modelo->modelo,
                ]
            );
        }
    }
}
