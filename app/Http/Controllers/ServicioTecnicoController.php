<?php

namespace App\Http\Controllers;

use App\Models\ControlStock;
use Illuminate\Http\Request;
use App\Models\ServicioTecnico;
use App\Models\User;
use App\Models\ActividadServicioTecnico;
use Carbon\Carbon;
use App\Models\Problema;
use Illuminate\Support\Facades\Log;

class ServicioTecnicoController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $filtro = $request->input('filtro');
        $fecha_desde = $request->input('fecha_desde');
        $fecha_hasta = $request->input('fecha_hasta');

        $servicioQuery = ServicioTecnico::with([
            'user:id,name,apellido',
            'modelo:id,nombre_modelo',
            'problema',
        ]);

        // Si solo tiene permiso de ver, filtrar por su user_id
        $user = auth()->user();
        $canGestionar = $user->can('gestionar servicio tecnico');
        $canVer = $user->can('ver servicio tecnico');

        if ($canVer && !$canGestionar) {
            $servicioQuery->where('user_id', $user->id);
        }

        if($fecha_desde && $fecha_hasta){
            $servicioQuery->where('created_at', '>=', Carbon::parse($fecha_desde)->startOfDay());
            $servicioQuery->where('created_at', '<=', Carbon::parse($fecha_hasta)->endOfDay());
        }

        $servicioQuery->whereIn('estado', 
            $filtro == 'FINALIZADOS' ? ['Finalizado'] : ['Pendiente', 'Urgente']
        );

        // Filtro por técnico si viene el parámetro
        if ($request->has('search') && is_numeric($request->input('search'))) {
            $servicioQuery->where('user_id', $request->input('search'));
        } else if (!empty($search)) {
            $servicioQuery->where(function ($query) use ($search) {
                $query->whereHas('modelo', function ($q) use ($search) {
                    $q->whereRaw('LOWER(nombre_modelo) LIKE ?', ["%{$search}%"]);
                })
                ->orWhereHas('user', function ($q) use ($search) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                    $q->orWhereRaw('LOWER(apellido) LIKE ?', ["%{$search}%"]); 
                })
                ->orWhereHas('problema', function ($q) use ($search) {
                    $q->whereRaw('LOWER(nombre) LIKE ?', ["%{$search}%"]);
                })
                ->orWhereRaw('LOWER(serie) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(factura) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(direccion) LIKE ?', ["%{$search}%"]);
            });
        }
        
        $servicios = $servicioQuery->latest()->get();
        $usuarios = User::role('tecnico')->get();

        $direcciones = ServicioTecnico::select('direccion')
            ->whereNotNull('direccion')
            ->where('direccion', '!=', '')
            ->distinct()
            ->orderBy('direccion')
            ->pluck('direccion');

        $problemas = Problema::all();

        return inertia('servicioTecnico/ServicioTecnico', [
            'servicios' => $servicios,
            'usuarios' => $usuarios,
            'filtro' => $filtro ?? 'EN PROCESO',
            'direcciones' => $direcciones,
            'problemas' => $problemas,
            'can' => [
                'ver' => auth()->user()->can('ver servicio tecnico'),
                'gestionar' => auth()->user()->can('gestionar servicio tecnico'),
            ]
        ]);
    }
    public function stock_by_n_serie(Request $request)
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

    public function create_servicio_tecnico(Request $request){
        
        $servicio = $request->validate([
            'modelo_id' => 'required',
            'factura' => 'required|string',
            'user_id' => 'required',
            'serie' => 'required|string',
            'fecha_salida' => 'required|string',
            'razon_social' => 'required|string',
            'dni_cuit' => 'required|string',
            'cliente_distribuidor' => 'required|string',
            'vendedor' => 'required|string',
            'direccion' => 'required|string',
            'contacto' => 'required|string',
            'localidad' => 'required|string',
            'provincia' => 'required|string',
            'telefono' => 'required|string',
            'email' => 'required|string',
            'horarios' => 'required|string',
            'problema_id' => 'required',
            'subproblema_id' => 'required',
            'interno_externo' => 'required|string',
        ]);
        
        try{
            ServicioTecnico::create($servicio);
        }catch (\Exception $e){
            Log::error($e->getMessage());
            return back()->withErrors([
                'message' => 'Ocurrio un error.'
            ]);
        }
    }

    public function update_servicio_tecnico(Request $request){
        
        $servicio = $request->validate([
            'id' => 'required',
            'modelo_id' => 'required',
            'factura' => 'required|string',
            'user_id' => 'required',
            'serie' => 'required|string',
            'fecha_salida' => 'required|string',
            'razon_social' => 'required|string',
            'dni_cuit' => 'required|string',
            'cliente_distribuidor' => 'required|string',
            'vendedor' => 'required|string',
            'direccion' => 'required|string',
            'provincia' => 'required|string',
            'telefono' => 'required|string',
            'contacto' => 'required|string',
            'localidad' => 'required|string',
            'email' => 'required|string',
            'horarios' => 'required|string',
            'problema_id' => 'required',
            'subproblema_id' => 'required',
            'interno_externo' => 'required|string',
            'importe' => 'required|numeric',
            'estado' => 'required|string|in:Finalizado,Pendiente,Urgente',
            'pagado' => 'required|boolean',
            'reinc' => 'required|string',
        ]);

        $servicio_tecnico = ServicioTecnico::where('id', $request->id)->first();
        if(!$servicio_tecnico){
            return back()->withErrors([
                'message' => 'Ocurrio un error.'
            ]);
        }

        $servicio_tecnico->update($servicio);
    }

    public function update_servicio_pagado(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'pagado' => 'required|boolean',
        ]);

        $servicio = ServicioTecnico::findOrFail($request->id);

        $servicio->update([
            'pagado' => $request->pagado, 
        ]);
    }

    public function update_servicio_estado(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'estado' => 'required|string|in:Finalizado,Pendiente,Urgente',
        ]);

        $servicio = ServicioTecnico::findOrFail($request->id);

        $servicio->update([
            'estado' => $request->estado, 
        ]);
    }

    public function servicio_tecnico_detalle(Request $request){
        $servicio_tecnico = ServicioTecnico::where('id', $request->input('id'))->first();
        if($servicio_tecnico){
            $servicio_tecnico->load(['modelo', 'user', 'actividades.user']);
        }

        $usuarios = User::role('tecnico')->get();

        $problemas = Problema::all();

        return inertia('servicioTecnico/ServicioTecnicoDetalle', [
            'servicio_tecnico' => $servicio_tecnico,
            'usuarios' => $usuarios,
            'problemas' => $problemas,
            'can' => [
                'ver' => auth()->user()->can('ver servicio tecnico'),
                'gestionar' => auth()->user()->can('gestionar servicio tecnico'),
            ]
        ]);
    }


    // ACTIVIDADES
    public function create_actividad_servicio_tecnico(Request $request){
        $validate = $request->validate([
            'servicio_tecnico_id' => 'required',
            'titulo' => 'required|string',
            'descripcion' => 'required|string',
        ]);

        ActividadServicioTecnico::create([
            'servicio_tecnico_id' => $request->servicio_tecnico_id,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
        ]);
    }

    public function update_actividad_servicio_tecnico(Request $request){
        $validate = $request->validate([
            'actividad_id' => 'required',
            'titulo' => 'required|string',
            'descripcion' => 'required|string',
        ]);

        $actividad_servicio_tecnico = ActividadServicioTecnico::where('id', $request->actividad_id)->first();
        if(!$actividad_servicio_tecnico){
            return back()->withErrors([
                'message' => 'Ocurrio un error.'
            ]);
        }

        $actividad_servicio_tecnico->update([
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
        ]);
    }
}
