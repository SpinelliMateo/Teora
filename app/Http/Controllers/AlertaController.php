<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\ControlStock;
use App\Models\Modelo;
use App\Models\User;
use App\Traits\RegistraActividades;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AlertaController extends Controller
{
    use RegistraActividades;

    public function index(Request $request)
    {
        $search = $request->input('search');
        $fecha_desde = $request->input('fecha_desde');
        $fecha_hasta = $request->input('fecha_hasta');
        $usuario = $request->input('usuario');
        $modelo = $request->input('modelo');
        $fecha_alerta_desde = $request->input('fecha_alerta_desde');
        $fecha_alerta_hasta = $request->input('fecha_alerta_hasta');

        $alertasQuery = Alerta::with([
            'user:id,name,apellido',
            'modelo:id,nombre_modelo',
        ]);

        if ($fecha_desde) {
            $alertasQuery->where('created_at', '>=', Carbon::parse($fecha_desde)->startOfDay());
        }
        if ($fecha_hasta) {
            $alertasQuery->where('created_at', '<=', Carbon::parse($fecha_hasta)->endOfDay());
        }

        if ($fecha_alerta_desde) {
            $alertasQuery->where('fecha_alerta', '>=', Carbon::parse($fecha_alerta_desde)->startOfDay());
        }
        if ($fecha_alerta_hasta) {
            $alertasQuery->where('fecha_alerta', '<=', Carbon::parse($fecha_alerta_hasta)->endOfDay());
        }

        if (!empty($usuario)) {
            $alertasQuery->where('user_id', $usuario);
        }

        if (!empty($modelo)) {
            $alertasQuery->where('modelo_id', $modelo);
        }

        if (!empty($search)) {
            $alertasQuery->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(motivo) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(serie) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('modelo', function ($q) use ($search) {
                        $q->whereRaw('LOWER(nombre_modelo) LIKE ?', ["%{$search}%"]);
                    })
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                          ->orWhereRaw('LOWER(apellido) LIKE ?', ["%{$search}%"]);
                    });
            });
        }

        $alertas = $alertasQuery->latest()->get();
        $usuarios = User::permission('alertas')->get();
        $modelos = Modelo::all();

        return inertia('alertas/Alertas', [
            'alertas' => $alertas,
            'usuarios' => $usuarios,
            'user_id' => $request->user() ? $request->user()->id : null,
            'modelos' => $modelos,
        ]);


    }
    public function get_modelo_by_serie(Request $request)
    {
        $request->validate([
            'n_serie' => 'required',
        ]);
        
        $control = ControlStock::where('n_serie', $request->n_serie)->where('fecha_salida', '!=', null)->first();

        if(!$control){
            return back();
        }

        return response()->json([
            'modelo' => $control->modelo, 
            'fecha_salida' => $control->fecha_salida, 
        ]);
    }
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'fecha_alerta' => 'required|date',
                'user_id' => 'required|exists:users,id',
                'serie' => 'required|string|max:255',
                'modelo_id' => 'required|exists:modelos,id',
                'motivo' => 'required|string|max:500',
            ], [
                'fecha_alerta.required' => 'La fecha de la alerta es obligatoria.',
                'fecha_alerta.date' => 'La fecha de la alerta no es válida.',
                'user_id.required' => 'El usuario es obligatorio.',
                'user_id.exists' => 'El usuario seleccionado no es válido.',
                'serie.required' => 'El número de serie es obligatorio.',
                'serie.max' => 'El número de serie no puede exceder 255 caracteres.',
                'modelo_id.required' => 'El modelo es obligatorio.',
                'modelo_id.exists' => 'El modelo seleccionado no es válido.',
                'motivo.required' => 'El motivo es obligatorio.',
                'motivo.max' => 'El motivo no puede exceder 500 caracteres.'
            ]);

            $alerta = Alerta::create($validated);

            $this->registrarCreacion(
                "Se creó la alerta #{$alerta->id} ({$alerta->motivo})",
                'alertas',
                $alerta->id
            );

            return redirect()->route('alertas')->with('success', 'Alerta creada exitosamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors([
                'error' => 'Por favor, revisa los datos ingresados. ' . collect($e->errors())->flatten()->first()
            ])->withInput();
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error inesperado al crear la alerta. Verifica que todos los datos sean correctos e intenta nuevamente.'
            ])->withInput();
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'fecha_alerta' => 'required|date',
                'user_id' => 'required|exists:users,id',
                'serie' => 'required|string|max:255',
                'modelo_id' => 'required|exists:modelos,id',
                'motivo' => 'required|string|max:500',
            ], [
                'fecha_alerta.required' => 'La fecha de la alerta es obligatoria.',
                'fecha_alerta.date' => 'La fecha de la alerta no es válida.',
                'user_id.required' => 'El usuario es obligatorio.',
                'user_id.exists' => 'El usuario seleccionado no es válido.',
                'serie.required' => 'El número de serie es obligatorio.',
                'serie.max' => 'El número de serie no puede exceder 255 caracteres.',
                'modelo_id.required' => 'El modelo es obligatorio.',
                'modelo_id.exists' => 'El modelo seleccionado no es válido.',
                'motivo.required' => 'El motivo es obligatorio.',
                'motivo.max' => 'El motivo no puede exceder 500 caracteres.'
            ]);

            $alerta = Alerta::findOrFail($id);
            $alerta->update($validated);
            
            $this->registrarModificacion(
                "Se modificó la alerta #{$alerta->id} ({$alerta->motivo})",
                'ordenes',
                $alerta->id
            );

            return redirect()->route('alertas')->with('success', 'Alerta actualizada exitosamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors([
                'error' => 'Por favor, revisa los datos ingresados. ' . collect($e->errors())->flatten()->first()
            ])->withInput();
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error inesperado al actualizar la alerta. Verifica que todos los datos sean correctos e intenta nuevamente.'
            ])->withInput();
        }
    }
    public function destroy($id)
    {
        try {
            $alerta = Alerta::findOrFail($id);
            $alerta->delete();

            $this->registrarEliminacion(
                "Se eliminó la alerta #{$alerta->id} ({$alerta->motivo})",
                'ordenes',
                $alerta->id
            );

            return redirect()->route('alertas')->with('success', '🗑️ Alerta eliminada exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error inesperado al eliminar la alerta. Por favor, intenta nuevamente o contacta al administrador.'
            ]);
        }
    }
    public function toggleSolucionado(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'solucionado' => 'required|boolean',
        ]);

        $alerta = Alerta::findOrFail($request->id);
        
        $alerta->update([
            'solucionado' => $request->solucionado,
        ]);


        if ($alerta->solucionado) {
                $this->registrarFinalizacion(
                    "Se finalizó la alerta #{$alerta->id} ({$alerta->motivo})",
                    'ordenes',
                    $alerta->id
                );
            return redirect()->route('alertas')->with('success', 'Alerta marcada como solucionada.');
        } else {
            $this->registrarModificacion(
                "Alerta pendiente: #{$alerta->id} ({$alerta->motivo})",
                'ordenes',
                $alerta->id
            );
            return redirect()->route('alertas')->with('success', 'Alerta marcada como no solucionada.');
        }

    }
}
