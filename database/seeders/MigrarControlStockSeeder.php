<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ControlStock;
use Carbon\Carbon;

class MigrarControlStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $control_stock = DB::table('control')->get();
        $control_stock = DB::table('control')
            ->orderBy('id', 'desc')  
            // ->limit(1000)             
            ->get();

        foreach ($control_stock as $control) {
            
            if ($control->modelo_id) {
                echo 'registrando el modelo ' . $control->modelo_id . '<br>';

                ControlStock::create([
                    'modelo_id'           => $control->modelo_id,
                    
                    'n_serie'           => $control->id,

                    'fecha_prearmado'     => $this->parseFecha($control->TimeArmado),
                    'prearmador'  => $control->OpArmado,

                    'fecha_inyectado'     => $this->parseFecha($control->fechainyectado),

                    'fecha_armado'        => $this->parseFecha($control->TimeMotor),
                    'armador'     => $control->OpMotor,

                    'fecha_embalado'      => $this->parseFecha($control->TimeEmbalado),
                    'embalador'   => $control->OpEmbalado,

                    'fecha_salida'      => $this->parseFecha($control->TimeSalida),

                    'equipo'      => $control->equipo,
                ]);
            }
        }
    }

    private function parseFecha($fecha)
    {
        return ($fecha && $fecha !== '0000-00-00 00:00:00') ? Carbon::parse($fecha) : null;
    }
}
