<?php

namespace App\Http\Controllers;

use App\Models\ControlStock;
use App\Models\OrdenFabricacion;
use App\Models\Alerta;
use App\Models\ActividadDashboard;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $metricas = $this->obtenerMetricas();
        $alertas = $this->obtenerAlertas();
        $entregas = $this->obtenerEntregas();
        $actividades = $this->obtenerActividades(); // Nueva función para actividades dinámicas
        
        return Inertia::render('Dashboard', [
            'metricas' => $metricas,
            'alertas' => $alertas,
            'entregas' => $entregas,
            'actividades' => $actividades
        ]);
    }
    
    public function obtenerDatosGraficos(Request $request)
    {
        $tipo = $request->get('tipo'); // 'prearmado', 'inyectado', 'armado', 'embalado'
        $fechaInicio = $request->get('fecha_inicio');
        $fechaFin = $request->get('fecha_fin');
        
        // Validar fechas
        if (!$fechaInicio || !$fechaFin) {
            $fechaFin = Carbon::now();
            $fechaInicio = Carbon::now()->subMonths(5); // Por defecto últimos 6 meses
        } else {
            $fechaInicio = Carbon::parse($fechaInicio);
            $fechaFin = Carbon::parse($fechaFin);
        }
        
        $datos = [];
        
        switch ($tipo) {
            case 'prearmado':
                $datos = $this->obtenerDatosPrearmado($fechaInicio, $fechaFin);
                break;
            case 'inyectado':
                $datos = $this->obtenerDatosInyectado($fechaInicio, $fechaFin);
                break;
            case 'armado':
                $datos = $this->obtenerDatosArmado($fechaInicio, $fechaFin);
                break;
            case 'embalado':
                $datos = $this->obtenerDatosEmbalado($fechaInicio, $fechaFin);
                break;
        }
        
        return response()->json([
            'datos' => $datos,
            'total' => collect($datos)->sum('value'),
            'fecha_inicio' => $fechaInicio->format('d/m/Y'),
            'fecha_fin' => $fechaFin->format('d/m/Y')
        ]);
    }
    
private function obtenerActividades()
{
    return ActividadDashboard::with('user')
        ->recientes(10) // Últimas 10 actividades
        ->get()
        ->map(function ($actividad) {
            return [
                'id' => $actividad->id,
                'fecha' => $actividad->created_at->format('d/m/Y'),
                'fecha_completa' => $actividad->created_at->format('d/m/Y H:i'),
                'descripcion' => $actividad->descripcion,
                'tipo' => $actividad->tipo,
                'usuario' => $actividad->user ? $actividad->user->name : 'Usuario desconocido',
                'modulo' => $actividad->modulo,
                'icono' => $actividad->icono,
                'color' => $actividad->color,
                'tiempo_transcurrido' => $actividad->created_at->diffForHumans(),
                'referencia_id' => $actividad->referencia_id,
                'referencia_tipo' => $actividad->referencia_tipo,
                'datos_adicionales' => $actividad->datos_adicionales
            ];
        });
}

// Método adicional para cargar más actividades via AJAX
public function obtenerActividadesRecientes(Request $request)
{
    $limite = $request->get('limite', 10);
    $offset = $request->get('offset', 0);
    
    $actividades = ActividadDashboard::with('user')
        ->orderBy('created_at', 'desc')
        ->offset($offset)
        ->limit($limite)
        ->get()
        ->map(function ($actividad) {
            return [
                'id' => $actividad->id,
                'fecha' => $actividad->created_at->format('d/m/Y'),
                'fecha_completa' => $actividad->created_at->format('d/m/Y H:i'),
                'descripcion' => $actividad->descripcion,
                'tipo' => $actividad->tipo,
                'usuario' => $actividad->user ? $actividad->user->name : 'Usuario desconocido',
                'modulo' => $actividad->modulo,
                'icono' => $actividad->icono,
                'color' => $actividad->color,
                'tiempo_transcurrido' => $actividad->created_at->diffForHumans(),
                'referencia_id' => $actividad->referencia_id,
                'referencia_tipo' => $actividad->referencia_tipo,
                'datos_adicionales' => $actividad->datos_adicionales
            ];
        });
    
    return response()->json($actividades);
}

// Método para crear una nueva actividad (opcional)
public function crearActividad(Request $request)
{
    $request->validate([
        'descripcion' => 'required|string|max:255',
        'tipo' => 'required|string|in:carga,modificacion,pausa,reanudacion,finalizacion,eliminacion,creacion,actualizacion',
        'modulo' => 'nullable|string|max:100',
        'referencia_id' => 'nullable|integer',
        'referencia_tipo' => 'nullable|string|max:100',
        'datos_adicionales' => 'nullable|array'
    ]);

    $actividad = ActividadDashboard::registrar(
        auth()->id(),
        $request->descripcion,
        $request->tipo,
        $request->modulo,
        $request->referencia_id,
        $request->referencia_tipo,
        $request->datos_adicionales
    );

    return response()->json([
        'success' => true,
        'actividad' => [
            'id' => $actividad->id,
            'fecha' => $actividad->created_at->format('d/m/Y'),
            'fecha_completa' => $actividad->created_at->format('d/m/Y H:i'),
            'descripcion' => $actividad->descripcion,
            'tipo' => $actividad->tipo,
            'usuario' => $actividad->user ? $actividad->user->name : 'Usuario desconocido',
            'modulo' => $actividad->modulo,
            'icono' => $actividad->icono,
            'color' => $actividad->color,
            'tiempo_transcurrido' => $actividad->created_at->diffForHumans()
        ]
    ]);
}

// Método para eliminar una actividad (opcional)
public function eliminarActividad($id)
{
    $actividad = ActividadDashboard::findOrFail($id);
    
    // Verificar que el usuario pueda eliminar la actividad
    if ($actividad->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
        return response()->json(['error' => 'No tienes permisos para eliminar esta actividad'], 403);
    }
    
    $actividad->delete();
    
    return response()->json(['success' => true]);
}
    private function obtenerAlertas()
    {
        $alertas = Alerta::with(['user', 'modelo'])
        ->where('solucionado', '!=', 1)
        ->orderBy('fecha_alerta', 'desc')
        ->get()
        ->map(function ($alerta) {
            $fechaAlerta = Carbon::parse($alerta->fecha_alerta);
            $diasTranscurridos = $fechaAlerta->diffInDays(Carbon::now());
            
            // Determinar el tipo según los días transcurridos
            $tipo = 'info';
            if ($diasTranscurridos > 40) {
                $tipo = 'error'; // Rojo
            }
            
            return [
                'id' => $alerta->id,
                'fecha_alerta' => $fechaAlerta->format('d/m/Y'),
                'usuario' => $alerta->user ? $alerta->user->name : 'N/A',
                'serie' => $alerta->serie,
                'modelo' => $alerta->modelo ? $alerta->modelo->modelo : 'N/A',
                'motivo' => $alerta->motivo,
                'tipo' => $tipo,
                'dias_transcurridos' => (int) $diasTranscurridos // Convertir a entero
            ];
        });
            
        return $alertas;
    }
    
    private function obtenerEntregas()
    {
        // Obtener todas las entregas
        $ordenes = OrdenFabricacion::with(['modelos', 'operarios'])
            ->whereNotNull('fecha_finalizacion')
            ->orderBy('fecha_finalizacion')
            ->get()
            ->map(function ($orden) {
                $fechaFinalizacion = Carbon::parse($orden->fecha_finalizacion);
                $hoy = Carbon::now();
                
                // Determinar estado visual - CORREGIDO CON ESTADOS REALES
                $estadoVisual = 'pendiente'; // Por defecto
                
                // Verificar el estado real de tu base de datos
                $estadoLower = strtolower($orden->estado ?? '');
                if ($estadoLower === 'completada') {
                    $estadoVisual = 'completado';
                } elseif ($fechaFinalizacion->isPast() && $fechaFinalizacion->lt($hoy->startOfDay())) {
                    $estadoVisual = 'vencido';
                }
                
                return [
                    'id' => $orden->id,
                    'no_orden' => $orden->no_orden,
                    'fecha_finalizacion' => $fechaFinalizacion->toDateString(),
                    'fecha_finalizacion_formato' => $fechaFinalizacion->format('d/m/Y'),
                    'modelos' => $orden->modelos->map(function($modelo) {
                        return [
                            'nombre' => $modelo->nombre_modelo ?? $modelo->modelo,
                            'modelo' => $modelo->modelo,
                            'cantidad' => $modelo->pivot->cantidad ?? 1
                        ];
                    })->toArray(),
                    'operarios' => $orden->operarios->map(function($operario) {
                        return [
                            'nombre' => $operario->nombre_completo ?? $operario->nombre,
                            'legajo' => $operario->n_legajo ?? $operario->legajo
                        ];
                    })->toArray(),
                    'estado' => $orden->estado,
                    'estado_visual' => $estadoVisual
                ];
            });
        
        // Estadísticas del mes actual - CORREGIDO
        $inicioMesActual = Carbon::now()->startOfMonth();
        $finMesActual = Carbon::now()->endOfMonth();
        $hoy = Carbon::now();
        
        // Debug: obtener todas las órdenes del mes para ver los estados
        $ordenesDelMes = OrdenFabricacion::whereNotNull('fecha_finalizacion')
            ->whereBetween('fecha_finalizacion', [$inicioMesActual, $finMesActual])
            ->get();
            
        $estadisticas = [
            'este_mes' => $ordenesDelMes->count(),
            'completadas' => $ordenesDelMes->filter(function($orden) {
                $estadoLower = strtolower($orden->estado ?? '');
                return $estadoLower === 'completada';
            })->count(),
            'pendientes' => $ordenesDelMes->filter(function($orden) use ($hoy) {
                $estadoLower = strtolower($orden->estado ?? '');
                $fechaFinalizacion = Carbon::parse($orden->fecha_finalizacion);
                return $estadoLower === 'pendiente' && $fechaFinalizacion->gte($hoy->startOfDay());
            })->count(),
            'vencidas' => $ordenesDelMes->filter(function($orden) use ($hoy) {
                $estadoLower = strtolower($orden->estado ?? '');
                $fechaFinalizacion = Carbon::parse($orden->fecha_finalizacion);
                return $estadoLower === 'pendiente' && $fechaFinalizacion->lt($hoy->startOfDay());
            })->count()
        ];
        
        // Debug log
        \Log::info('Estadísticas entregas:', [
            'total_ordenes_mes' => $ordenesDelMes->count(),
            'estadisticas' => $estadisticas,
            'estados_encontrados' => $ordenesDelMes->pluck('estado')->unique()->toArray()
        ]);
        
        return [
            'ordenes' => $ordenes,
            'estadisticas' => $estadisticas
        ];
    }

    public function obtenerEntregasPorMes(Request $request)
    {
        $mes = $request->get('mes', Carbon::now()->month);
        $anio = $request->get('anio', Carbon::now()->year);
        
        $inicioMes = Carbon::create($anio, $mes, 1)->startOfMonth();
        $finMes = Carbon::create($anio, $mes, 1)->endOfMonth();
        
        \Log::info("Buscando entregas para: mes={$mes}, año={$anio}, desde={$inicioMes}, hasta={$finMes}");
        
        $entregas = OrdenFabricacion::with(['modelos', 'operarios'])
            ->whereNotNull('fecha_finalizacion')
            ->whereBetween('fecha_finalizacion', [$inicioMes, $finMes])
            ->get();
            
        \Log::info("Entregas encontradas: " . $entregas->count());
        
        $entregasAgrupadas = $entregas->groupBy(function ($orden) {
                return Carbon::parse($orden->fecha_finalizacion)->day;
            })
            ->map(function ($ordenes, $dia) {
                return $ordenes->map(function ($orden) {
                    $fechaFinalizacion = Carbon::parse($orden->fecha_finalizacion);
                    $hoy = Carbon::now();
                    
                    // Determinar estado visual - USANDO LA MISMA LÓGICA CON ESTADOS REALES
                    $estadoVisual = 'pendiente';
                    $estadoLower = strtolower($orden->estado ?? '');
                    
                    if ($estadoLower === 'completada') {
                        $estadoVisual = 'completado';
                    } elseif ($fechaFinalizacion->isPast() && $fechaFinalizacion->lt($hoy->startOfDay())) {
                        $estadoVisual = 'vencido';
                    }
                    
                    return [
                        'id' => $orden->id,
                        'no_orden' => $orden->no_orden,
                        'fecha_finalizacion' => $fechaFinalizacion->format('d/m/Y'),
                        'modelos' => $orden->modelos->map(function($modelo) {
                            return [
                                'nombre' => $modelo->nombre_modelo ?? $modelo->modelo,
                                'modelo' => $modelo->modelo,
                                'cantidad' => $modelo->pivot->cantidad ?? 1
                            ];
                        })->toArray(),
                        'operarios' => $orden->operarios->map(function($operario) {
                            return [
                                'nombre' => $operario->nombre_completo ?? $operario->nombre,
                                'legajo' => $operario->n_legajo ?? $operario->legajo
                            ];
                        })->toArray(),
                        'estado' => $orden->estado,
                        'estado_visual' => $estadoVisual
                    ];
                });
            });
            
        \Log::info("Entregas agrupadas: ", $entregasAgrupadas->toArray());
            
        return response()->json($entregasAgrupadas);
    }
    
    private function obtenerDatosPrearmado($fechaInicio, $fechaFin)
    {
        // Para prearmado, usamos las órdenes de fabricación que no tienen control_stock
        // Agrupamos por mes de creación de la orden
        $resultados = OrdenFabricacion::select(
                DB::raw('YEAR(created_at) as anio'),
                DB::raw('MONTH(created_at) as mes'),
                DB::raw('COUNT(*) as cantidad')
            )
            ->whereDoesntHave('controlStock')
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->groupBy('anio', 'mes')
            ->orderBy('anio')
            ->orderBy('mes')
            ->get();
            
        return $this->formatearDatosPorMes($resultados, $fechaInicio, $fechaFin);
    }
    
    private function obtenerDatosInyectado($fechaInicio, $fechaFin)
    {
        // Registros que completaron prearmado en el rango de fechas
        $resultados = ControlStock::select(
                DB::raw('YEAR(fecha_prearmado) as anio'),
                DB::raw('MONTH(fecha_prearmado) as mes'),
                DB::raw('COUNT(*) as cantidad')
            )
            ->whereNotNull('fecha_prearmado')
            ->whereBetween('fecha_prearmado', [$fechaInicio, $fechaFin])
            ->groupBy('anio', 'mes')
            ->orderBy('anio')
            ->orderBy('mes')
            ->get();
            
        return $this->formatearDatosPorMes($resultados, $fechaInicio, $fechaFin);
    }
    
    private function obtenerDatosArmado($fechaInicio, $fechaFin)
    {
        // Registros que completaron inyectado en el rango de fechas
        $resultados = ControlStock::select(
                DB::raw('YEAR(fecha_inyectado) as anio'),
                DB::raw('MONTH(fecha_inyectado) as mes'),
                DB::raw('COUNT(*) as cantidad')
            )
            ->whereNotNull('fecha_inyectado')
            ->whereBetween('fecha_inyectado', [$fechaInicio, $fechaFin])
            ->groupBy('anio', 'mes')
            ->orderBy('anio')
            ->orderBy('mes')
            ->get();
            
        return $this->formatearDatosPorMes($resultados, $fechaInicio, $fechaFin);
    }
    
    private function obtenerDatosEmbalado($fechaInicio, $fechaFin)
    {
        // Registros que completaron armado en el rango de fechas
        $resultados = ControlStock::select(
                DB::raw('YEAR(fecha_armado) as anio'),
                DB::raw('MONTH(fecha_armado) as mes'),
                DB::raw('COUNT(*) as cantidad')
            )
            ->whereNotNull('fecha_armado')
            ->whereBetween('fecha_armado', [$fechaInicio, $fechaFin])
            ->groupBy('anio', 'mes')
            ->orderBy('anio')
            ->orderBy('mes')
            ->get();
            
        return $this->formatearDatosPorMes($resultados, $fechaInicio, $fechaFin);
    }
    
    private function formatearDatosPorMes($resultados, $fechaInicio, $fechaFin)
    {
        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 
                  'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        
        $datos = [];
        $fechaActual = $fechaInicio->copy()->startOfMonth();
        
        while ($fechaActual->lte($fechaFin)) {
            $cantidad = $resultados->where('anio', $fechaActual->year)
                                 ->where('mes', $fechaActual->month)
                                 ->first();
            
            $datos[] = [
                'label' => $meses[$fechaActual->month - 1] . ' ' . $fechaActual->format('y'),
                'value' => $cantidad ? $cantidad->cantidad : 0,
                'date' => $fechaActual->format('d/m/Y')
            ];
            
            $fechaActual->addMonth();
        }
        
        return $datos;
    }
    
    private function obtenerMetricas()
    {
        // Prearmado: Órdenes de fabricación sin control_stock asociado
        $prearmado = OrdenFabricacion::whereDoesntHave('controlStock')
            ->count();
            
        // Inyectado: Registros con fecha_prearmado pero sin fecha_inyectado
        $inyectado = ControlStock::whereNotNull('fecha_prearmado')
            ->whereNull('fecha_inyectado')
            ->count();
            
        // Armado: Registros con fecha_inyectado pero sin fecha_armado
        $armado = ControlStock::whereNotNull('fecha_inyectado')
            ->whereNull('fecha_armado')
            ->count();
            
        // Embalado: Registros con fecha_armado pero sin fecha_embalado
        $embalado = ControlStock::whereNotNull('fecha_armado')
            ->whereNull('fecha_embalado')
            ->count();
            
        return [
            'prearmado' => $prearmado,
            'inyectado' => $inyectado,
            'armado' => $armado,
            'embalado' => $embalado
        ];
    }
}