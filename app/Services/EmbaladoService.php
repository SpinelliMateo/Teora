<?php

namespace App\Services;

use App\Contracts\ControlStockRepositoryInterface;
use App\Contracts\OperarioRepositoryInterface;
use App\Models\ControlStock;
use App\Models\OrdenFabricacion;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EmbaladoService
{
    private ControlStockRepositoryInterface $controlStockRepository;
    private OperarioRepositoryInterface $operarioRepository;

    public function __construct(
        ControlStockRepositoryInterface $controlStockRepository,
        OperarioRepositoryInterface $operarioRepository
    ) {
        $this->controlStockRepository = $controlStockRepository;
        $this->operarioRepository = $operarioRepository;
    }

    public function validarProductosParaEmbalado(array $numerosSerie): array
    {
        $resultados = [];
        $productosValidos = [];
        $productosYaEmbalados = [];
        $productosNoEncontrados = [];
        $productosInvalidos = [];

        foreach ($numerosSerie as $numeroSerie) {
            if (empty(trim($numeroSerie))) {
                continue;
            }

            $controlStock = $this->controlStockRepository->findByNumeroSerie($numeroSerie);

            if (!$controlStock) {
                $productosNoEncontrados[] = $numeroSerie;
                continue;
            }

            // Verificar si ya fue embalado
            if (!is_null($controlStock->fecha_embalado)) {
                $productosYaEmbalados[] = [
                    'numero_serie' => $numeroSerie,
                    'producto' => $controlStock->load('modelo'),
                    'fecha_embalado' => $controlStock->fecha_embalado
                ];
                continue;
            }

            // Verificar si está válido para embalado (debe estar armado)
            if (!$this->isValidForEmbalado($controlStock)) {
                $productosInvalidos[] = $numeroSerie;
                continue;
            }

            $productosValidos[] = $controlStock->load('modelo');
        }

        $success = count($productosNoEncontrados) === 0 && count($productosInvalidos) === 0;

        return [
            'success' => $success,
            'productos_validos' => $productosValidos,
            'productos_ya_embalados' => $productosYaEmbalados,
            'productos_no_encontrados' => $productosNoEncontrados,
            'productos_invalidos' => $productosInvalidos,
            'tiene_productos_ya_embalados' => count($productosYaEmbalados) > 0
        ];
    }

    public function procesarEmbalado(array $numerosSerie, int $operarioId): array
    {
        // Primero validar todos los productos
        $validacion = $this->validarProductosParaEmbalado($numerosSerie);

        if (!$validacion['success']) {
            $mensajes = [];
            
            if (count($validacion['productos_no_encontrados']) > 0) {
                $mensajes[] = '❌ No se encontraron los siguientes números de serie: ' . implode(', ', $validacion['productos_no_encontrados']);
            }
            
            if (count($validacion['productos_invalidos']) > 0) {
                $mensajes[] = '⚠️ Los siguientes productos no están en condiciones para ser embalados: ' . implode(', ', $validacion['productos_invalidos']);
            }

            return [
                'success' => false,
                'message' => implode(' ', $mensajes),
                'data' => $validacion
            ];
        }

        // Procesar embalado de productos válidos
        $productosEmbalados = [];
        $ordenesCompletadas = []; // Para rastrear qué órdenes se completaron
        
        DB::transaction(function () use ($validacion, $operarioId, &$productosEmbalados, &$ordenesCompletadas) {
            foreach ($validacion['productos_validos'] as $controlStock) {
                // Marcar como embalado
                $this->marcarComoEmbalado($controlStock, $operarioId);
                $productosEmbalados[] = $controlStock;
                
                // Verificar si la orden de fabricación está completa
                if ($controlStock->orden_fabricacion_id) {
                    $ordenCompletada = $this->verificarYCompletarOrden($controlStock->orden_fabricacion_id);
                    if ($ordenCompletada) {
                        $ordenesCompletadas[] = $ordenCompletada;
                    }
                }
            }
        });

        $cantidadEmbalados = count($productosEmbalados);
        $message = $cantidadEmbalados === 1 
            ? "✅ 1 producto embalado exitosamente."
            : "✅ {$cantidadEmbalados} productos embalados exitosamente.";

        // Agregar información sobre órdenes completadas
        if (count($ordenesCompletadas) > 0) {
            $ordenesInfo = collect($ordenesCompletadas)->map(function($orden) {
                return "Orden {$orden->no_orden}";
            })->implode(', ');
            
            $message .= " 🎉 Se completaron las siguientes órdenes de fabricación: {$ordenesInfo}";
        }

        return [
            'success' => true,
            'message' => $message,
            'data' => [
                'productos_embalados' => $productosEmbalados,
                'productos_ya_embalados' => $validacion['productos_ya_embalados'],
                'ordenes_completadas' => $ordenesCompletadas
            ]
        ];
    }

    // CORRECCIÓN DEL PROBLEMA DE CONTEO
    public function obtenerEstadisticasEmbaladoHoy(): array
    {
        $hoy = Carbon::today();
        
        // Query corregida para evitar duplicados
        $embaladosHoy = DB::table('control_stock')
            ->join('procesos_operarios', 'control_stock.id', '=', 'procesos_operarios.control_stock_id')
            ->join('operarios', 'procesos_operarios.operario_embalador_id', '=', 'operarios.id')
            ->whereDate('control_stock.fecha_embalado', $hoy)
            ->whereNotNull('control_stock.fecha_embalado')
            ->whereNotNull('procesos_operarios.operario_embalador_id')
            ->select(
                'operarios.nombre as operario',
                DB::raw('COUNT(DISTINCT control_stock.id) as cantidad_embalados') // DISTINCT para evitar duplicados
            )
            ->groupBy('operarios.id', 'operarios.nombre')
            ->orderByDesc('cantidad_embalados')
            ->get();

        $totalEmbalados = $embaladosHoy->sum('cantidad_embalados');

        return [
            'operarios' => $embaladosHoy,
            'total' => $totalEmbalados
        ];
    }

    public function obtenerOperariosEmbaladores(): Collection
    {
        return $this->operarioRepository->getOperariosEmbaladores();
    }

    public function obtenerProductosEmbaladosParaEtiquetas(array $controlStockIds): Collection
    {
        return $this->controlStockRepository->getByIds($controlStockIds)
            ->map(function ($controlStock) {
                return [
                    'id' => $controlStock->id,
                    'n_serie' => $controlStock->n_serie,
                    'modelo' => [
                        'modelo_nombre' => $controlStock->modelo->nombre_modelo ?? $controlStock->modelo->modelo ?? 'Sin modelo'
                    ],
                    'fecha_embalado' => $controlStock->fecha_embalado ? 
                        Carbon::parse($controlStock->fecha_embalado)->format('d/m/Y H:i') : 
                        'No embalado',
                    'fecha_embalado_iso' => $controlStock->fecha_embalado,
                    'qr_code' => $controlStock->n_serie // Para el código de barras
                ];
            });
    }

    /**
     * Verificar si una orden de fabricación está completada y marcarla como tal
     */
    private function verificarYCompletarOrden(int $ordenFabricacionId): ?OrdenFabricacion
    {
        $orden = OrdenFabricacion::find($ordenFabricacionId);
        
        if (!$orden || $orden->estado === 'completada') {
            return null; // La orden no existe o ya está completada
        }

        // Verificar si todos los productos de la orden están embalados
        $todosEmbalados = $orden->controlStock()
            ->whereNull('fecha_embalado')
            ->doesntExist(); // Si no existen productos sin embalar, entonces todos están embalados

        if ($todosEmbalados) {
            $orden->update([
                'estado' => 'completada',
                'fecha_finalizacion' => Carbon::now()
            ]);
            
            return $orden->fresh(); // Retornar la orden actualizada
        }

        return null;
    }

    private function isValidForEmbalado(ControlStock $controlStock): bool
    {
        return !is_null($controlStock->fecha_armado) && 
               is_null($controlStock->fecha_embalado);
    }

    private function marcarComoEmbalado(ControlStock $controlStock, int $operarioId): void
    {
        // Actualizar control_stock
        $controlStock->update([
            'fecha_embalado' => Carbon::now()
        ]);

        // Crear o actualizar registro en procesos_operarios
        $procesoExistente = DB::table('procesos_operarios')
            ->where('control_stock_id', $controlStock->id)
            ->first();

        if ($procesoExistente) {
            DB::table('procesos_operarios')
                ->where('control_stock_id', $controlStock->id)
                ->update([
                    'operario_embalador_id' => $operarioId,
                    'updated_at' => now()
                ]);
        } else {
            DB::table('procesos_operarios')->insert([
                'control_stock_id' => $controlStock->id,
                'operario_embalador_id' => $operarioId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}