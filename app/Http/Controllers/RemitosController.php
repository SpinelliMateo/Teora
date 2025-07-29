<?php

namespace App\Http\Controllers;

use App\Models\Modelo;
use App\Models\Remito;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RemitosController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $filtro = $request->input('filtro');

        $remitos = Remito::with(['modelos']);

        if($filtro == 'FINALIZADOS'){
            $remitos->where('estado', 'finalizado');
        }else{
            $remitos->whereNot('estado', 'finalizado');
        }

        if ($search) {
            $remitos->where(function ($query) use ($search) {
            $query->whereRaw('LOWER(n_remito) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(cliente) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(estado) LIKE ?', ["%{$search}%"])
                ->orWhereHas('modelos', function ($q) use ($search) {
                $q->whereRaw('LOWER(modelo) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(nombre_modelo) LIKE ?', ["%{$search}%"]);
                });
            });
        }

        $remitos->orderByDesc('n_remito');
        
        $modelos = Modelo::orderBy('modelo', 'asc')->get();
        
        // Obtener el próximo número de remito
        $ultimoRemito = Remito::orderByDesc('n_remito')->first();
        $proximoNumero = $ultimoRemito ? (int)$ultimoRemito->n_remito + 1 : 1;
        $proximoNumeroFormateado = str_pad($proximoNumero, 8, '0', STR_PAD_LEFT);

        return inertia('remitos/Remitos', [
            'remitos' => $remitos->paginate(20),
            'filtro' => $filtro ?? 'EN PROCESO',
            'search' => $search,
            'modelos' => $modelos,
            'proximo_numero' => $proximoNumeroFormateado,
            'can' => [
                'ver' => auth()->user()->can('ver remitos'),
                'gestionar' => auth()->user()->can('gestionar remitos'),
            ]
        ]);
    }
    public function updateDespachado(Request $request, $id)
    {
        try {
            $remito = Remito::findOrFail($id);
            
            if ($remito->estado === 'finalizado') {
                return back()->withErrors([
                    'error' => 'No se puede modificar el estado del remito N°' . $remito->n_remito . ' porque ya está finalizado.'
                ]);
            }
            
            $estadoAnterior = $remito->estado;
            $remito->estado = $remito->estado === 'despachado' ? 'procesado' : 'despachado';
            $remito->save();
            
            if ($remito->estado === 'despachado') {
                $mensaje = '✅ Remito N°' . $remito->n_remito . ' marcado como DESPACHADO exitosamente. El remito ahora pasó a la sección de despacho del sistema.';
            } else {
                $mensaje = '🔄 Remito N°' . $remito->n_remito . ' regresó al estado PROCESADO. Ahora puede ser editado nuevamente.';
            }
            
            return back()->with([
                'success' => true,
                'message' => $mensaje
            ]);
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error inesperado al cambiar el estado del remito. Por favor, intenta nuevamente o contacta al administrador.'
            ]);
        }
    }
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'n_remito' => 'required|string|max:255',
                'cliente' => 'required|string|max:255',
                'modelos' => 'required|array|min:1',
                'modelos.*.modelo_id' => 'required|exists:modelos,id',
                'modelos.*.cantidad' => 'required|integer|min:1',
            ], [
                'n_remito.required' => 'El número de remito es obligatorio.',
                'cliente.required' => 'El nombre del cliente es obligatorio.',
                'cliente.max' => 'El nombre del cliente no puede exceder 255 caracteres.',
                'modelos.required' => 'Debe seleccionar al menos un modelo.',
                'modelos.min' => 'Debe incluir al menos un modelo en el remito.',
                'modelos.*.modelo_id.required' => 'Debe seleccionar un modelo válido.',
                'modelos.*.modelo_id.exists' => 'El modelo seleccionado no es válido.',
                'modelos.*.cantidad.required' => 'La cantidad es obligatoria.',
                'modelos.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
                'modelos.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
            ]);

            // Verificar si ya existe un remito con ese número
            $remitoExistente = Remito::where('n_remito', $validated['n_remito'])->first();
            if ($remitoExistente) {
                return back()->withErrors([
                    'error' => 'Ya existe un remito con el número ' . $validated['n_remito'] . '. Por favor, use un número diferente.'
                ])->withInput();
            }

            // Verificar modelos duplicados
            $modelosIds = array_column($validated['modelos'], 'modelo_id');
            $modelosDuplicados = array_diff_assoc($modelosIds, array_unique($modelosIds));
            if (!empty($modelosDuplicados)) {
                return back()->withErrors([
                    'error' => 'No se pueden seleccionar modelos duplicados en el mismo remito. Por favor, revisa tu selección.'
                ])->withInput();
            }

            // Crear el remito
            $remito = Remito::create([
                'n_remito' => $validated['n_remito'],
                'cliente' => $validated['cliente'],
                'estado' => 'procesado',
            ]);

            // Preparar los datos para la tabla pivot
            $modelosData = [];
            $totalModelos = 0;
            foreach ($validated['modelos'] as $modelo) {
                $modelosData[$modelo['modelo_id']] = [
                    'cantidad' => $modelo['cantidad']
                ];
                $totalModelos += $modelo['cantidad'];
            }

            // Asociar los modelos al remito
            $remito->modelos()->attach($modelosData);

            // Recalcular el próximo número para el siguiente remito
            $proximoNumero = (int)$remito->n_remito + 1;
            $proximoNumeroFormateado = str_pad($proximoNumero, 8, '0', STR_PAD_LEFT);

            $cantidadModelos = count($validated['modelos']);
            $mensaje = '🎉 ¡Remito N°' . $remito->n_remito . ' creado exitosamente! ' . 
                      'Cliente: ' . $remito->cliente . ' | ' . 
                      $cantidadModelos . ' modelo(s) diferentes | ' . 
                      $totalModelos . ' unidades totales.';

            return back()->with([
                'success' => true,
                'message' => $mensaje,
                'proximo_numero' => $proximoNumeroFormateado
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors([
                'error' => 'Por favor, revisa los datos ingresados. ' . collect($e->errors())->flatten()->first()
            ])->withInput();
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error inesperado al crear el remito. Verifica que todos los datos sean correctos e intenta nuevamente.'
            ])->withInput();
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'n_remito' => 'required|string|max:255',
                'cliente' => 'required|string|max:255',
                'modelos' => 'required|array|min:1',
                'modelos.*.modelo_id' => 'required|exists:modelos,id',
                'modelos.*.cantidad' => 'required|integer|min:1',
            ], [
                'n_remito.required' => 'El número de remito es obligatorio.',
                'cliente.required' => 'El nombre del cliente es obligatorio.',
                'cliente.max' => 'El nombre del cliente no puede exceder 255 caracteres.',
                'modelos.required' => 'Debe seleccionar al menos un modelo.',
                'modelos.min' => 'Debe incluir al menos un modelo en el remito.',
                'modelos.*.modelo_id.required' => 'Debe seleccionar un modelo válido.',
                'modelos.*.modelo_id.exists' => 'El modelo seleccionado no es válido.',
                'modelos.*.cantidad.required' => 'La cantidad es obligatoria.',
                'modelos.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
                'modelos.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
            ]);

            $remito = Remito::findOrFail($id);
            
            // Verificar si el remito está finalizado
            if ($remito->estado === 'finalizado' || $remito->estado === 'despachado') {
                return back()->withErrors([
                    'error' => 'No se puede editar el remito N°' . $remito->n_remito . ' porque ya está finalizado. Solo los remitos en proceso pueden ser modificados.'
                ]);
            }
            
            // Verificar modelos duplicados
            $modelosIds = array_column($validated['modelos'], 'modelo_id');
            $modelosDuplicados = array_diff_assoc($modelosIds, array_unique($modelosIds));
            if (!empty($modelosDuplicados)) {
                return back()->withErrors([
                    'error' => 'No se pueden seleccionar modelos duplicados en el mismo remito. Por favor, revisa tu selección.'
                ])->withInput();
            }
            
            // Actualizar los datos básicos del remito
            $remito->update([
                'n_remito' => $validated['n_remito'],
                'cliente' => $validated['cliente'],
            ]);

            // Desvincula todos los modelos actuales
            $remito->modelos()->detach();

            // Preparar los datos para la tabla pivot
            $modelosData = [];
            $totalModelos = 0;
            foreach ($validated['modelos'] as $modelo) {
                $modelosData[$modelo['modelo_id']] = [
                    'cantidad' => $modelo['cantidad']
                ];
                $totalModelos += $modelo['cantidad'];
            }

            // Asociar los nuevos modelos al remito
            $remito->modelos()->attach($modelosData);

            $cantidadModelos = count($validated['modelos']);
            $mensaje = '✏️ Remito N°' . $remito->n_remito . ' actualizado exitosamente! ' . 
                      'Cliente: ' . $remito->cliente . ' | ' . 
                      $cantidadModelos . ' modelo(s) diferentes | ' . 
                      $totalModelos . ' unidades totales.';

            return back()->with([
                'success' => true,
                'message' => $mensaje
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors([
                'error' => 'Por favor, revisa los datos ingresados. ' . collect($e->errors())->flatten()->first()
            ])->withInput();
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error inesperado al actualizar el remito. Verifica que todos los datos sean correctos e intenta nuevamente.'
            ])->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $remito = Remito::findOrFail($id);
            
            // Verificar si el remito está finalizado
            if ($remito->estado === 'finalizado' || $remito->estado === 'despachado') {
                return back()->withErrors([
                    'error' => 'No se puede eliminar el remito N°' . $remito->n_remito . ' porque ya está finalizado. Solo los remitos en proceso pueden ser eliminados.'
                ]);
            }
            
            $numeroRemito = $remito->n_remito;
            $cliente = $remito->cliente;
            
            // Eliminar las relaciones con modelos
            $remito->modelos()->detach();
            
            // Eliminar el remito
            $remito->delete();

            $mensaje = '🗑️ Remito N°' . $numeroRemito . ' eliminado exitosamente. ' . 
                      'Cliente: ' . $cliente . ' ha sido removido del sistema permanentemente.';

            return back()->with([
                'success' => true,
                'message' => $mensaje
            ]);
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error inesperado al eliminar el remito. Por favor, intenta nuevamente o contacta al administrador.'
            ]);
        }
    }
}