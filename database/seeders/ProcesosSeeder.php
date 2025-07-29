<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ProcesosOperarios;
use App\Models\Operario;
use Carbon\Carbon;

class ProcesosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $armadores = DB::table('armadores')->get();
        // $prearmadores = DB::table('prearmadores')->get();
        // $embaladores = DB::table('embaladores')->get();

        $control_stock = DB::table('control_stock')->get();

        foreach ($control_stock as $control) {
            
            $operario_armador_id = null;
            $operario_prearmador_id = null;
            $operario_embalador_id = null;
            if($control->armador > 0 ){
                $armador = DB::table('armadores')->where('legajo', $control->armador)->first();
                if($armador) $operario = Operario::where('nombre', $armador->nombre)->first();

                if ($operario) {
                    $operario_armador_id = $operario->id;
                }
            }
            if($control->prearmador > 0 ){
                $prearmador = DB::table('prearmadores')->where('legajo', $control->prearmador)->first();
                if($prearmador) $operario = Operario::where('nombre', $prearmador->nombre)->first();

                if ($operario) {
                    $operario_prearmador_id = $operario->id;
                }
            }
            if($control->embalador > 0 ){
                $embalador = DB::table('embaladores')->where('legajo', $control->embalador)->first();
                if($embalador) $operario = Operario::where('nombre', $embalador->nombre)->first();

                $operario = Operario::where('nombre', $embalador->nombre)->first();

                if ($operario) {
                    $operario_embalador_id = $operario->id;
                }
            }

            ProcesosOperarios::create([
                'control_stock_id' => $control->id,
                'operario_armador_id' => $operario_armador_id,
                'operario_prearmador_id' => $operario_prearmador_id,
                'operario_embalador_id' => $operario_embalador_id,
            ]); 
        }
    }
}
