<?php

namespace App\Http\Controllers;

use App\Models\ControlStock;
use App\Models\Modelo;
use App\Models\Operario;
use App\Models\Problema;
use App\Models\ServicioTecnico;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $modelos = Modelo::orderBy('nombre_modelo', 'asc')->get();
        $operarios = Operario::orderBy('nombre', 'asc')->get();

        $tecnicos = User::role('tecnico')->orderBy('name', 'asc')->get();


        $problemas = Problema::with('subproblemas')
            ->orderBy('nombre', 'asc')
            ->get();

        // Obtener datos para el gráfico de procesos completados
        $datosGraficoProcesos = $this->obtenerDatosProcesos($request);
        
        // Obtener datos para el gráfico de servicios técnicos
        $datosGraficoServicios = $this->obtenerDatosServicios($request);

        return inertia('reportes/Reportes', [
            'modelos' => $modelos,
            'operarios' => $operarios,
            'tecnicos' => $tecnicos,
            'problemas' => $problemas,
            'datosGraficoProcesos' => $datosGraficoProcesos,
            'datosGraficoServicios' => $datosGraficoServicios
        ]);
    }

    private function obtenerDatosProcesos(Request $request)
    {
        $query = ControlStock::whereNotNull('fecha_salida')
            ->with(['modelo', 'procesos.operario_armador', 'procesos.operario_prearmador', 'procesos.operario_embalador']);

        // Filtro por modelo
        if ($request->filled('modelo_id')) {
            $query->where('modelo_id', $request->modelo_id);
        }

        // Filtro por operario (busca en cualquiera de los roles del proceso)
        if ($request->filled('operario_id')) {
            $query->whereHas('procesos', function($q) use ($request) {
                $q->where('operario_armador_id', $request->operario_id)
                  ->orWhere('operario_prearmador_id', $request->operario_id)
                  ->orWhere('operario_embalador_id', $request->operario_id);
            });
        }

        // Filtro por fecha de salida
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_salida', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_salida', '<=', $request->fecha_hasta);
        }

        $procesos = $query->get();

        // Agrupar por modelo y contar
        $datosPorModelo = $procesos->groupBy('modelo.nombre_modelo')
            ->map(function($grupo) {
                return [
                    'modelo' => $grupo->first()->modelo->nombre_modelo,
                    'cantidad' => $grupo->count()
                ];
            })
            ->values()
            ->toArray();

        return $datosPorModelo;
    }

    private function obtenerDatosServicios(Request $request)
    {
        $query = ServicioTecnico::where('estado', 'finalizado')
            ->with(['modelo', 'user', 'problema', 'subproblema']);

        // Filtro por modelo
        if ($request->filled('modelo_id')) {
            $query->where('modelo_id', $request->modelo_id);
        }

        // Filtro por técnico (user_id en ServicioTecnico)
        if ($request->filled('tecnico_id')) {
            $query->where('user_id', $request->tecnico_id);
        }

        // Filtro por problema
        if ($request->filled('problema_id')) {
            $query->where('problema_id', $request->problema_id);
        }

        // Filtro por subproblema
        if ($request->filled('subproblema_id')) {
            $query->where('subproblema_id', $request->subproblema_id);
        }

        // Filtro por fecha de salida
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_salida', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_salida', '<=', $request->fecha_hasta);
        }

        $servicios = $query->get();

        // Agrupar por modelo y contar
        $datosPorModelo = $servicios->groupBy('modelo.nombre_modelo')
            ->map(function($grupo) {
                return [
                    'modelo' => $grupo->first()->modelo->nombre_modelo,
                    'cantidad' => $grupo->count()
                ];
            })
            ->values()
            ->toArray();

        return $datosPorModelo;
    }
}
