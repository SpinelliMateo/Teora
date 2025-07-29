<?php

namespace App\Http\Controllers;

use App\Models\ControlStock;
use App\Models\Operario;
use Illuminate\Http\Request;
use App\Models\ProcesosOperarios;
use Illuminate\Support\Facades\Log;

class ProcesosOperariosController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $filtro = $request->input('filtro');
        // $fecha_desde = $request->input('fecha_desde');
        // $fecha_hasta = $request->input('fecha_hasta');
        
        $procesos_query = ProcesosOperarios::with(['control_stock.modelo', 'operario_armador', 'operario_prearmador', 'operario_embalador'])
        ->whereHas('control_stock', function ($query) {
            $query->whereNotNull('fecha_prearmado');
        });

        // if($fecha_desde && $fecha_hasta){
        //     $servicioQuery->where('created_at', '>=', Carbon::parse($fecha_desde)->startOfDay());
        //     $servicioQuery->where('created_at', '<=', Carbon::parse($fecha_hasta)->endOfDay());
        // }

        if($filtro == 'FINALIZADOS'){
            $procesos_query->whereHas('control_stock', function ($query) {
                $query->whereNotNull('fecha_salida');
            });
        }else{
            $procesos_query->whereHas('control_stock', function ($query) {
                $query->whereNull('fecha_salida');
            });
        }

        if (!empty($search)) {
            $procesos_query->where(function ($query) use ($search) {
                $query->whereHas('control_stock', function ($q) use ($search) {
                    $q->whereRaw('LOWER(n_serie) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(equipo) LIKE ?', ["%{$search}%"]);
                })
                ->orWhereHas('control_stock.modelo', function ($q) use ($search) {
                    $q->whereRaw('LOWER(modelo) LIKE ?', ["%{$search}%"]);
                })
                ->orWhereHas('operario_armador', function ($q) use ($search) {
                    $q->whereRaw('LOWER(nombre) LIKE ?', ["%{$search}%"]);
                })
                ->orWhereHas('operario_prearmador', function ($q) use ($search) {
                    $q->whereRaw('LOWER(nombre) LIKE ?', ["%{$search}%"]);
                })
                ->orWhereHas('operario_embalador', function ($q) use ($search) {
                    $q->whereRaw('LOWER(nombre) LIKE ?', ["%{$search}%"]);
                });
            }); 
        }
        
        $operarios = Operario::orderBy('nombre', 'asc')->get();

        return inertia('procesos/Procesos', [
            'procesos' => $procesos_query->paginate(10),
            'filtro' => $filtro ?? 'EN PROCESO',
            'search' => $search,
            'operarios' => $operarios,
            'can' => [
                'ver' => auth()->user()->can('ver servicio proceso'),
                'gestionar' => auth()->user()->can('gestionar servicio proceso'),
            ] 
        ]);
    }

    public function update_proceso(Request $request){
        $validate = $request->validate([
            'id' => 'required',
            'fecha_prearmado' => 'nullable|string',
            'hora_prearmado' => 'nullable|string',
            'operario_prearmado' => 'nullable|integer|exists:operarios,id',
            'fecha_inyectado' => 'nullable|string',
            'hora_inyectado' => 'nullable|string',
            'fecha_armado' => 'nullable|string',
            'hora_armado' => 'nullable|string',
            'operario_armado' => 'nullable|integer|exists:operarios,id',
            'numero_motor' => 'nullable|string|max:50',
            'fecha_embalado' => 'nullable|string',
            'hora_embalado' => 'nullable|string',
            'operario_embalado' => 'nullable|integer|exists:operarios,id',
        ]);
        // Validar jerarquía
        if (
            ($request->fecha_inyectado || $request->hora_inyectado) &&
            !$request->fecha_prearmado
        ) {
            return back()->withErrors([
                'message' => 'Debe ingresar el prearmado antes que el inyectado.'
            ]);
        }

        if (
            ($request->fecha_armado || $request->hora_armado) &&
            !$request->fecha_inyectado
        ) {
            return back()->withErrors([
                'message' => 'Debe ingresar el inyectado antes que el armado.'
            ]);
        }

        if (
            ($request->fecha_embalado || $request->hora_embalado) &&
            !$request->fecha_armado
        ) {
            return back()->withErrors([
                'message' => 'Debe ingresar el armado antes que el embalado.'
            ]);
        }

        try{
            $control_stock = ControlStock::findOrFail($request->id);

            $control_stock->update([
                'fecha_prearmado' => $this->combineDateTime($request->fecha_prearmado, $request->hora_prearmado),
                'fecha_inyectado' => $this->combineDateTime($request->fecha_inyectado, $request->hora_inyectado),
                'fecha_armado' => $this->combineDateTime($request->fecha_armado, $request->hora_armado),
                'fecha_embalado' => $this->combineDateTime($request->fecha_embalado, $request->hora_embalado),
                'equipo' => $request->numero_motor,
            ]);
            // Actualizar operarios (incluyendo el caso de borrar/setear a null)
            if($request->has('operario_prearmado') || $request->has('operario_armado') || $request->has('operario_embalado')){
                $proceso = ProcesosOperarios::where('control_stock_id', $control_stock->id)->first();
                
                if($request->has('operario_prearmado')){
                    $proceso->operario_prearmador_id = $request->operario_prearmado;
                }
                if($request->has('operario_armado')){
                    $proceso->operario_armador_id = $request->operario_armado;
                }
                if($request->has('operario_embalado')){
                    $proceso->operario_embalador_id = $request->operario_embalado;
                }
                $proceso->save();
            }

            return back()->with('success', 'Proceso actualizado correctamente.');

        }catch (\Exception $e){
            Log::error($e->getMessage());
            return back()->withErrors([
                'message' => 'Ocurrio un error.'
            ]);
        }
    }

    private function combineDateTime($date, $time)
    {
        if ($date && $time) {
            return "$date $time:00"; // ejemplo: "2025-06-24 14:30:00"
        } elseif ($date) {
            return "$date 00:00:00"; // solo fecha, sin hora
        }

        return null; // ninguno presente
    }
}
