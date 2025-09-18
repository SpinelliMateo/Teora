<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Maquina;
use App\Models\Modelo;
use App\Models\Operario;
use App\Models\OrdenFabricacion;
use App\Traits\RegistraActividades;
use Illuminate\Validation\Rule;

class OrdenFabricacionController extends Controller
{
    use RegistraActividades;

    public function index()
    {
        $modelos = Modelo::orderBy('modelo', 'ASC')->get(); 
        $ordenesFabricacion = OrdenFabricacion::withSum('modelos as total_modelos', 'modelo_orden_fabricacion.cantidad')->orderBy('id', 'DESC')->get(); 

        return Inertia::render('ordenesFabricacion/Index', [
            'modelos' => $modelos,
            'ordenesFabricacion'=> $ordenesFabricacion,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fecha' => 'required|date',
            'fecha_finalizacion' => 'required|date',
            'no_orden' => 'required|string|max:255',
            'modelo_productos' => 'required|array|min:1',
            'modelo_productos.*.modelo_id' => 'required|exists:modelos,id',
            'modelo_productos.*.cantidad' => 'required|integer|min:1',
        ]); 
    
        $orden = OrdenFabricacion::create([
            'fecha' => $validated['fecha'],
            'fecha_finalizacion' => $validated['fecha_finalizacion'],
            'no_orden' => $validated['no_orden']
        ]); 
    
        $dataParaSync = [];
    
        foreach($validated['modelo_productos'] as $modelo){
            $dataParaSync[$modelo['modelo_id']] = ['cantidad' => $modelo['cantidad']]; 
        }
    
        $orden->modelos()->sync($dataParaSync);
    
        // Cargar la relación completa con los datos de pivot
        $nuevaOrden = OrdenFabricacion::withSum('modelos as total_modelos', 'modelo_orden_fabricacion.cantidad')->find($orden->id);
    
            $this->registrarCreacion(
            "Se creó la orden #{$orden->no_orden}",
            'ordenes',
            $orden->id
        );

        return response()->json(['orden' => $nuevaOrden]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        $modelos = Modelo::orderBy('modelo', 'ASC')->get(); 
        $ordenFabricacion = OrdenFabricacion::with(['operarios', 'modelos'])
        ->withSum('modelos as total_modelos', 'modelo_orden_fabricacion.cantidad')
        ->find($id);
        $operarios = Operario::activos()->orderBy('nombre')->get();
        
        return Inertia::render('ordenesFabricacion/Edit', [
            'modelos' => $modelos,
            'modelosOrden' => $ordenFabricacion->modelos,
            'ordenFabricacion' => $ordenFabricacion,
            'operarios' => $operarios,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $ordenFabricacion = OrdenFabricacion::findOrFail($id);

        $request->validate([
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'fecha_finalizacion' => ['required', 'date', 'after:fecha'],
            'no_orden' => [
                'required', 
                'string', 
                Rule::unique('ordenes_fabricacion', 'no_orden')->ignore($ordenFabricacion->id)
            ],
            'operarios' => ['required', 'array', 'min:1'],
            'operarios.*' => ['exists:operarios,id'],
        ], [
            'fecha.after_or_equal' => 'La fecha debe ser de hoy en adelante.',
            'fecha_finalizacion.after' => 'La fecha de finalización debe ser posterior a la fecha de inicio.',
            'operarios.required' => 'Debe seleccionar al menos un operario.',
            'operarios.min' => 'Debe seleccionar al menos un operario.',
        ]);

        $ordenFabricacion->update([
            'fecha' => $request->fecha,
            'fecha_finalizacion' => $request->fecha_finalizacion,
            'no_orden' => $request->no_orden,
        ]);

        $ordenFabricacion->operarios()->sync($request->operarios);

        $this->registrarModificacion(
            "Se modificó la orden #{$ordenFabricacion->no_orden}",
            'ordenes',
            $ordenFabricacion->id
        );


        return redirect()->back()->with('success', 'Orden de fabricación actualizada exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function agregarModelo(Request $request, $id)
    {
        $request->validate([
            'modelo_id' => 'required|exists:modelos,id',
            'cantidad' => 'required|integer|min:1'
        ]);
    
        try {
            $orden = OrdenFabricacion::findOrFail($id);
            
            // Verificar si el modelo ya existe en la orden
            $existeModelo = $orden->modelos()->where('modelo_id', $request->modelo_id)->exists();
            
            if ($existeModelo) {
                return back()->withErrors(['modelo_id' => 'Este modelo ya está agregado a la orden']);
            }
    
            // Agregar el modelo a la orden (tabla pivot)
            $orden->modelos()->attach($request->modelo_id, [
                'cantidad' => $request->cantidad,
                'created_at' => now(),
                'updated_at' => now()
            ]);
    
            return back()->with('success', 'Modelo agregado correctamente a la orden');
            
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al agregar el modelo: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Actualizar la cantidad de un modelo en la orden
     * PUT /ordenes-fabricacion/{ordenId}/modelos/{modeloId}
     */
    public function actualizarModelo(Request $request, $ordenId, $modeloId)
    {
        $request->validate([
            'cantidad' => 'required|integer|min:1'
        ]);
    
        try {
            $orden = OrdenFabricacion::findOrFail($ordenId);
            
            // Actualizar en la tabla pivot
            $orden->modelos()->updateExistingPivot($modeloId, [
                'cantidad' => $request->cantidad,
                'updated_at' => now()
            ]);
            
    
            return back()->with('success', 'Cantidad actualizada correctamente');
            
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al actualizar el modelo: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Eliminar un modelo de la orden de fabricación
     * DELETE /ordenes-fabricacion/{ordenId}/modelos/{modeloId}
     */
    public function eliminarModelo($ordenId, $modeloId)
    {
        try {
            $orden = OrdenFabricacion::findOrFail($ordenId);
            
            // Eliminar de la tabla pivot
            $orden->modelos()->detach($modeloId);
    
            return back()->with('success', 'Modelo eliminado correctamente de la orden');
            
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al eliminar el modelo: ' . $e->getMessage()]);
        }
    }

}
