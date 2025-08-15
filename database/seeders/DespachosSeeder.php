<?php

namespace Database\Seeders;

use App\Models\ControlRemito;
use App\Models\ControlStock;
use App\Models\DespachoFinalizado;
use App\Models\DespachoRemito;
use App\Models\Remito;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DespachosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //TODO EL CONTENIDO DE ESTE SEEDER ES FICTICIO, SE UTILIZA PARA PROBAR LA VISTA DE HISTORIAL DE DESPACHOS.
        //UNA VEZ HECHA LA CONEXION DE LA PARTE DESPACHO OPERARIO CON LA PARTE DE DESPACHO ADMINISTRATIVO, SE DEBE ELIMINAR ESTE SEEDER.

        $despachos = [
            ['numero_despacho' => 'DESP-0001'],
            ['numero_despacho' => 'DESP-0002'],
            ['numero_despacho' => 'DESP-0003'],
        ];

        foreach ($despachos as $despacho) {
            DespachoFinalizado::create($despacho);
        }

        $remitos = Remito::take(7)->get();

        DespachoRemito::create([
            'despacho_id' => DespachoFinalizado::where('numero_despacho', 'DESP-0001')->first()->id,
            'remito_id' => $remitos[0]->id,
        ]);
        DespachoRemito::create([
            'despacho_id' => DespachoFinalizado::where('numero_despacho', 'DESP-0001')->first()->id,
            'remito_id' => $remitos[1]->id,
        ]);
        DespachoRemito::create([
            'despacho_id' => DespachoFinalizado::where('numero_despacho', 'DESP-0002')->first()->id,
            'remito_id' => $remitos[2]->id,
        ]);
        DespachoRemito::create([
            'despacho_id' => DespachoFinalizado::where('numero_despacho', 'DESP-0002')->first()->id,
            'remito_id' => $remitos[3]->id,
        ]);
        DespachoRemito::create([
            'despacho_id' => DespachoFinalizado::where('numero_despacho', 'DESP-0003')->first()->id,
            'remito_id' => $remitos[4]->id,
        ]);
        DespachoRemito::create([
            'despacho_id' => DespachoFinalizado::where('numero_despacho', 'DESP-0003')->first()->id,
            'remito_id' => $remitos[5]->id,
        ]);
        DespachoRemito::create([
            'despacho_id' => DespachoFinalizado::where('numero_despacho', 'DESP-0003')->first()->id,
            'remito_id' => $remitos[6]->id,
        ]);

        ControlRemito::create([
            'remito_id' => Remito::where('id', 1)->first()->id,
            'control_stock_id' => ControlStock::where('id', 447439)->first()->id,
        ]); 
        ControlRemito::create([
            'remito_id' => Remito::where('id', 1)->first()->id,
            'control_stock_id' => ControlStock::where('id', 447089)->first()->id,
        ]);
        ControlRemito::create([
            'remito_id' => Remito::where('id', 28)->first()->id,
            'control_stock_id' => ControlStock::where('id', 447236)->first()->id,
        ]);
        ControlRemito::create([
            'remito_id' => Remito::where('id', 29)->first()->id,
            'control_stock_id' => ControlStock::where('id', 515078)->first()->id,
        ]);
        ControlRemito::create([
            'remito_id' => Remito::where('id', 29)->first()->id,
            'control_stock_id' => ControlStock::where('id', 467607)->first()->id,
        ]);
        ControlRemito::create([
            'remito_id' => Remito::where('id', 29)->first()->id,
            'control_stock_id' => ControlStock::where('id', 529828)->first()->id,
        ]);
        ControlRemito::create([
            'remito_id' => Remito::where('id', 30)->first()->id,
            'control_stock_id' => ControlStock::where('id', 448374)->first()->id,
        ]);
        ControlRemito::create([
            'remito_id' => Remito::where('id', 30)->first()->id,
            'control_stock_id' => ControlStock::where('id', 538165)->first()->id,
        ]);
        ControlRemito::create([
            'remito_id' => Remito::where('id', 31)->first()->id,
            'control_stock_id' => ControlStock::where('id', 534441)->first()->id,
        ]);
        ControlRemito::create([
            'remito_id' => Remito::where('id', 32)->first()->id,
            'control_stock_id' => ControlStock::where('id', 449607)->first()->id,
        ]);
        ControlRemito::create([
            'remito_id' => Remito::where('id', 32)->first()->id,
            'control_stock_id' => ControlStock::where('id', 448829)->first()->id,
        ]);
        ControlRemito::create([
            'remito_id' => Remito::where('id', 32)->first()->id,
            'control_stock_id' => ControlStock::where('id', 447512)->first()->id,
        ]);
        ControlRemito::create([
            'remito_id' => Remito::where('id', 33)->first()->id,
            'control_stock_id' => ControlStock::where('id', 536113)->first()->id,
        ]);
    }
}
