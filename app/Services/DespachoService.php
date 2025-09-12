<?php

namespace App\Services;

use App\Contracts\ControlStockRepositoryInterface;
use App\Contracts\RemitoRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DespachoService
{
    private ControlStockRepositoryInterface $controlStockRepository;
    private RemitoRepositoryInterface $remitoRepository;

    public function __construct(
        ControlStockRepositoryInterface $controlStockRepository,
        RemitoRepositoryInterface $remitoRepository
    ) {
        $this->controlStockRepository = $controlStockRepository;
        $this->remitoRepository = $remitoRepository;
    }

    public function obtenerRemitosParaDespacho(): Collection
    {
        return $this->remitoRepository->getByEstado('despachado');
    }

    public function buscarControlStockPorSerie(string $numeroSerie): array
    {
        $controlStock = $this->controlStockRepository->findByNumeroSerie($numeroSerie);

        if (!$controlStock) {
            return [
                'success' => false,
                'message' => 'Número de serie no encontrado'
            ];
        }

        if (!$this->controlStockRepository->isValidForDespacho($controlStock)) {
            return [
                'success' => false,
                'message' => 'Este producto no está disponible para despacho'
            ];
        }

        return [
            'success' => true,
            'message' => 'Producto encontrado y agregado correctamente',
            'data' => [
                'id' => $controlStock->id,
                'n_serie' => $controlStock->n_serie,
                'fecha_embalado' => $controlStock->fecha_embalado,
                'modelo_id' => $controlStock->modelo_id,
                'modelo_nombre' => $controlStock->modelo->nombre_modelo ?? 'N/A'
            ]
        ];
    }

    public function obtenerModelosAgrupados(array $remitoIds): array
    {
        $modelos = $this->remitoRepository->getModelosPorRemito($remitoIds);
        
        $modelosAgrupados = $modelos->groupBy('id')->map(function ($modelosGrupo) {
            $modelo = $modelosGrupo->first();
            $cantidadTotal = $modelosGrupo->sum('pivot.cantidad');
            
            return [
                'modelo_id' => $modelo->id,
                'nombre_modelo' => $modelo->nombre_modelo,
                'cantidad_total' => $cantidadTotal,
                'cantidad_cargada' => 0,
                'cantidad_restante' => $cantidadTotal
            ];
        });

        return array_values($modelosAgrupados->toArray());
    }

    public function procesarDespacho(array $remitoIds, array $controlStockIds): array
    {
        try {
            DB::beginTransaction();

            $validacion = $this->validarCantidadesCompletas($remitoIds, $controlStockIds);
            if (!$validacion['valido']) {
                return [
                    'success' => false,
                    'message' => $validacion['mensaje']
                ];
            }
            
            $this->actualizarFechaSalidaControlStock($controlStockIds);

            $this->finalizarRemitos($remitoIds);

            $this->crearHistorialDespacho($remitoIds, $controlStockIds);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Despacho procesado correctamente'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Error al procesar el despacho: ' . $e->getMessage()
            ];
        }
    }

    private function validarCantidadesCompletas(array $remitoIds, array $controlStockIds): array
    {
        $modelosRequeridos = $this->obtenerModelosAgrupados($remitoIds);
        
        $controlStockItems = $this->controlStockRepository->getByIds($controlStockIds);
        $modelosCargados = $controlStockItems->groupBy('modelo_id')->map(function ($items) {
            return $items->count();
        });

        foreach ($modelosRequeridos as $modeloRequerido) {
            $cantidadCargada = $modelosCargados->get($modeloRequerido['modelo_id'], 0);
            
            if ($cantidadCargada !== $modeloRequerido['cantidad_total']) {
                return [
                    'valido' => false,
                    'mensaje' => "El modelo {$modeloRequerido['nombre_modelo']} no tiene la cantidad correcta. Requerido: {$modeloRequerido['cantidad_total']}, Cargado: {$cantidadCargada}"
                ];
            }
        }

        return ['valido' => true, 'mensaje' => ''];
    }

    private function actualizarFechaSalidaControlStock(array $controlStockIds): void
    {
        $this->controlStockRepository->updateFechaSalida($controlStockIds, Carbon::now());
    }

    private function finalizarRemitos(array $remitoIds): void
    {
        $this->remitoRepository->updateEstado($remitoIds, 'finalizado');
    }
    private function crearHistorialDespacho(array $remitoIds, array $controlStockIds): void
    {
        $this->remitoRepository->crearHistorial($remitoIds, $controlStockIds);
    }
}