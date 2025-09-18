<?php

namespace App\Http\Controllers;

use App\Models\Modelo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;
use App\Traits\RegistraActividades;

class ModeloController extends Controller
{
    use RegistraActividades;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Modelo::query();

        // Búsqueda
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('modelo', 'like', '%' . $searchTerm . '%')
                  ->orWhere('nombre_modelo', 'like', '%' . $searchTerm . '%')
                  ->orWhere('tension', 'like', '%' . $searchTerm . '%')
                  ->orWhere('sistema', 'like', '%' . $searchTerm . '%')
                  ->orWhere('gas', 'like', '%' . $searchTerm . '%');
            });
        }

        $modelos = $query->orderBy('created_at', 'desc')->get();

        return Inertia::render('configuracion/Modelos/Index', [
            'modelos' => $modelos
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('configuracion/Modelos/Create_edit', [
            'isEdit' => false
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'modelo' => 'required|string|max:255|unique:modelos,modelo',
            'nombre_modelo' => 'required|string|max:255',
            'tension' => 'nullable|string|max:255',
            'frecuencia' => 'nullable|string|max:255',
            'corriente' => 'nullable|string|max:255',
            'potencia' => 'nullable|string|max:255',
            'aislacion' => 'nullable|string|max:255',
            'sistema' => 'nullable|string|max:255',
            'volumen' => 'nullable|string|max:255',
            'espumante' => 'nullable|string|max:255',
            'clase' => 'nullable|string|max:255',
            'gas' => 'nullable|string|max:255',
            'cantidad_gas' => 'nullable|string|max:255',
        ], [
            'modelo.required' => 'El número de modelo es obligatorio.',
            'modelo.unique' => 'Ya existe un modelo con este número.',
            'nombre_modelo.required' => 'El nombre del modelo es obligatorio.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $modelo = Modelo::create($request->all());

            $this->registrarCreacion(
                "Se creó el modelo {$modelo->nombre_modelo}",
                'modelos', // módulo
                $modelo->id  // ID de referencia
            );

            return redirect()->route('modelos')
                ->with('success', 'Modelo creado correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Error al crear el modelo: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Modelo $modelo)
    {
        return Inertia::render('modelos/Show', [
            'modelo' => $modelo
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Modelo $modelo)
    {
        return Inertia::render('configuracion/Modelos/Create_edit', [
            'modelo' => $modelo,
            'isEdit' => true
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Modelo $modelo)
    {
        $validator = Validator::make($request->all(), [
            'modelo' => 'required|string|max:255|unique:modelos,modelo,' . $modelo->id,
            'nombre_modelo' => 'required|string|max:255',
            'tension' => 'nullable|string|max:255',
            'frecuencia' => 'nullable|string|max:255',
            'corriente' => 'nullable|string|max:255',
            'potencia' => 'nullable|string|max:255',
            'aislacion' => 'nullable|string|max:255',
            'sistema' => 'nullable|string|max:255',
            'volumen' => 'nullable|string|max:255',
            'espumante' => 'nullable|string|max:255',
            'clase' => 'nullable|string|max:255',
            'gas' => 'nullable|string|max:255',
            'cantidad_gas' => 'nullable|string|max:255',
        ], [
            'modelo.required' => 'El número de modelo es obligatorio.',
            'modelo.unique' => 'Ya existe un modelo con este número.',
            'nombre_modelo.required' => 'El nombre del modelo es obligatorio.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $modelo->update($request->all());

                $this->registrarModificacion(
                    "Se modificó el modelo {$modelo->nombre_modelo}",
                    'modelos',
                    $modelo->id
                );

            return redirect()->route('modelos')
                ->with('success', 'Modelo actualizado correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Error al actualizar el modelo: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Modelo $modelo)
    {
        try {
            // Verificar si el modelo está siendo usado en otras tablas
            $enUso = false;
            $mensaje = '';

            // Verificar en órdenes de fabricación
            if ($modelo->ordenesFabricacion()->count() > 0) {
                $enUso = true;
                $mensaje .= 'órdenes de fabricación, ';
            }

            // Verificar en control de stock
            if ($modelo->stock()->count() > 0) {
                $enUso = true;
                $mensaje .= 'registros de stock, ';
            }

            // Verificar en stock mínimo
            if ($modelo->stock_minimo) {
                $enUso = true;
                $mensaje .= 'configuración de stock mínimo, ';
            }

            // Verificar en servicio técnico
            if ($modelo->sercicio_tecnico) {
                $enUso = true;
                $mensaje .= 'servicio técnico, ';
            }

            // Verificar en remitos
            if ($modelo->remitos()->count() > 0) {
                $enUso = true;
                $mensaje .= 'remitos, ';
            }

            if ($enUso) {
                $mensaje = rtrim($mensaje, ', ');
                return redirect()->back()
                    ->withErrors(['error' => 'No se puede eliminar el modelo porque está siendo usado en: ' . $mensaje . '.']);
            }

            $this->registrarEliminacion(
                "Se eliminó el modelo {$modelo->nombre_modelo}",
                'modelos',
                $modelo->id
            );
            
            $modelo->delete();

            return redirect()->route('modelos')
                ->with('success', 'Modelo eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Error al eliminar el modelo: ' . $e->getMessage()]);
        }
    }
}