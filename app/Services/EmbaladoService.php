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

    public function validarSerieParaEmbalado(string $numeroSerie): array
    {
        $controlStock = $this->controlStockRepository->findByNumeroSerie($numeroSerie);

        if (!$controlStock) {
            return [
                'success' => false,
                'message' => 'No se encontró el modelo con el número de serie: ' . $numeroSerie
            ];
        }

        if (!is_null($controlStock->fecha_embalado)) {
            return [
                'success' => false,
                'message' => 'El modelo con el número de serie ' . $numeroSerie . ' ya fue embalado.'
            ];
        }

        if (!$this->isValidForEmbalado($controlStock)) {
            return [
                'success' => false,
                'message' => 'El modelo con el número de serie ' . $numeroSerie . ' no está en condiciones para ser embalado.'
            ];
        }

        return [
            'success' => true,
            'message' => 'El número de serie ' . $numeroSerie . ' con el modelo ' . $controlStock->modelo->nombre_modelo . ' es válido para ser embalado.',
            'data' => $controlStock 
        ];
    }


    public function procesarEmbalado(array $numerosSerie, int $operarioId)
    {

        $productosEmbalados = [];

        DB::transaction(function () use ($numerosSerie, $operarioId, &$productosEmbalados) {
            foreach ($numerosSerie as $serie) {
                $validacion = $this->validarSerieParaEmbalado($serie);
                if (!$validacion['success']) {
                    continue;
                }

                $controlStock = $validacion['data'];
                // Marcar como embalado
                $this->marcarComoEmbalado($controlStock, $operarioId);
                $productosEmbalados[] = $controlStock;
                
                if ($controlStock->orden_fabricacion_id) {
                    $this->verificarYCompletarOrden($controlStock->orden_fabricacion_id);
                }
            }
        });

        $cantidadEmbalados = count($productosEmbalados);
        $message = $cantidadEmbalados === 1 
            ? "✅ 1 producto embalado exitosamente."
            : "✅ {$cantidadEmbalados} productos embalados exitosamente.";


        return [
            'success' => true,
            'message' => $message,
            'data' => [
                'productos_embalados' => $productosEmbalados
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

    private function verificarYCompletarOrden(int $ordenFabricacionId): ?OrdenFabricacion
    {
        $orden = OrdenFabricacion::find($ordenFabricacionId);
        
        if (!$orden || $orden->estado === 'completada') {
            return null; // La orden no existe o ya está completada
        }

        $todosEmbalados = $orden->controlStock()->whereNull('fecha_embalado')->get();

        if ($todosEmbalados->isEmpty()) {
            $orden->update([
                'estado' => 'completada',
                'fecha_finalizacion' => Carbon::now()
            ]); 
            return $orden;
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
        $controlStock->update([
            'fecha_embalado' => Carbon::now()
        ]);

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