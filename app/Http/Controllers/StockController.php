<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stock;
use App\Models\ControlStock;
use App\Models\Modelo;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request){
        $fecha = $request->input('fecha');
        $search = $request->input('search');

        try {
            $fechaParsed = $fecha
                ? Carbon::createFromFormat('Y-m-d\TH:i', $fecha)
                : Carbon::now()->subMonths(4);
        } catch (\Exception $e) {
            Log::error('Error al parsear la fecha: ' . $e->getMessage());
            $fechaParsed = Carbon::now()->subMonths(4);
        }

        $stockQuery = ControlStock::query()
            ->where('fecha_prearmado', '>=', $fechaParsed)
            ->whereNull('fecha_salida')
            ->where('oculto', false)
            ->selectRaw('
                modelo_id,
                COUNT(CASE 
                    WHEN fecha_embalado IS NOT NULL THEN 1
                    WHEN fecha_armado IS NOT NULL THEN 2
                    WHEN fecha_inyectado IS NOT NULL THEN 3
                    WHEN fecha_prearmado IS NOT NULL THEN 4
                END) as total, -- opcional: total por modelo

                COUNT(CASE WHEN fecha_embalado IS NOT NULL THEN 1 END) as conteo_embalado,
                COUNT(CASE WHEN fecha_armado IS NOT NULL AND fecha_embalado IS NULL THEN 1 END) as conteo_armado,
                COUNT(CASE WHEN fecha_inyectado IS NOT NULL AND fecha_armado IS NULL AND fecha_embalado IS NULL THEN 1 END) as conteo_inyectado,
                COUNT(CASE WHEN fecha_prearmado IS NOT NULL AND fecha_inyectado IS NULL AND fecha_armado IS NULL AND fecha_embalado IS NULL THEN 1 END) as conteo_prearmado
            ')
            ->groupBy('modelo_id')
            ->with('modelo.stock_minimo');


        if (!empty($search)) {
            $search = mb_strtolower($search); // convertir el término de búsqueda a minúsculas en PHP

            $stockQuery->whereHas('modelo', function ($query) use ($search) {
                $query->where(DB::raw('LOWER(modelo)'), 'like', "%{$search}%")
                    ->orWhere(DB::raw('LOWER(nombre_modelo)'), 'like', "%{$search}%");
            });
        }

        $stock = $stockQuery->paginate(10);

        return inertia('stock/Stock', [
            'stock' => $stock,
            'fecha' => $fecha,
        ]);
    }
    
    public function stock_detalle(Request $request)
    {
        $fecha = $request->input('fecha');
        $filtro = $request->input('filtro');
    
        $fechaParsed = $fecha 
            ? Carbon::createFromFormat('Y-m-d\TH:i', $fecha) 
            : Carbon::now()->subMonths(4);
      
        $nombre_modelo = $request->input('nombre_modelo');

        $modelo = Modelo::where('nombre_modelo', $nombre_modelo)->first();
        
        if (!$modelo) {
            return back()->withErrors([
                'message' => 'El modelo no fue encontrado.'
            ]);
        }
        $stock_detalle_query = ControlStock::where('modelo_id', $modelo->id)
            ->where('fecha_prearmado', '>=', $fechaParsed)
            ->whereNull('fecha_salida')
            ->orderBy('fecha_prearmado', 'desc');
    
        $stock_detalle = null;
        if($filtro == 'OCULTOS'){
            $stock_detalle = $stock_detalle_query->where('oculto', true)->get();
        }
        else if($filtro == 'NO OCULTOS'){
            $stock_detalle = $stock_detalle_query->where('oculto', false)->get();
        }
        else{

            $stock_detalle = $stock_detalle_query->get();
        }

        
        
        return inertia('stock/StockDetalle', [
            'modelo' => $modelo,
            'stock_detalle' => $stock_detalle,
            'filtro' => $filtro ?? 'TODOS',
            'fecha' => $fecha,
        ]);
    }

    public function ocultar_stock_detalle(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'oculto' => 'required|boolean',
        ]);

        $stock_detalle = ControlStock::findOrFail($request->id);

        $stock_detalle->update([
            'oculto' => $request->oculto, 
        ]);
    }
}