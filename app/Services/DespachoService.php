<?php

namespace App\Services;

use App\Contracts\RemitoRepositoryInterface;
use App\Contracts\ControlStockRepositoryInterface;
use App\Models\ControlStock;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class DespachoService
{
    private RemitoRepositoryInterface $remitoRepository;
    private ControlStockRepositoryInterface $controlStockRepository;

    public function __construct(
        RemitoRepositoryInterface $remitoRepository,
        ControlStockRepositoryInterface $controlStockRepository
    ) {
        $this->remitoRepository = $remitoRepository;
        $this->controlStockRepository = $controlStockRepository;
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
                'message' => 'No se encontró un registro con ese número de serie',
                'data' => null
            ];
        }

        if (!$this->controlStockRepository->isValidForDespacho($controlStock)) {
            $message = $this->obtenerMensajeError($controlStock);
            return [
                'success' => false,
                'message' => $message,
                'data' => null
            ];
        }

        return [
            'success' => true,
            'message' => 'Registro agregado correctamente',
            'data' => [
                'id' => $controlStock->id,
                'n_serie' => $controlStock->n_serie,
                'fecha_embalado' => $controlStock->fecha_embalado,
                'modelo_id' => $controlStock->modelo_id,
                'modelo_nombre' => $controlStock->modelo->nombre_modelo
            ]
        ];
    }

    public function obtenerModelosAgrupados(array $remitoIds): SupportCollection
    {
        if (empty($remitoIds)) {
            return collect();
        }

        $remitos = $this->remitoRepository->getByEstado('despachado')
            ->whereIn('id', $remitoIds);

        $modelosAgrupados = [];

        foreach ($remitos as $remito) {
            foreach ($remito->modelos as $modelo) {
                $modeloId = $modelo->id;
                $cantidad = $modelo->pivot->cantidad;

                if (isset($modelosAgrupados[$modeloId])) {
                    $modelosAgrupados[$modeloId]['cantidad_total'] += $cantidad;
                    $modelosAgrupados[$modeloId]['cantidad_restante'] += $cantidad;
                } else {
                    $modelosAgrupados[$modeloId] = [
                        'modelo_id' => $modeloId,
                        'nombre_modelo' => $modelo->nombre_modelo,
                        'cantidad_total' => $cantidad,
                        'cantidad_cargada' => 0,
                        'cantidad_restante' => $cantidad
                    ];
                }
            }
        }

        return collect(array_values($modelosAgrupados));
    }

    private function obtenerMensajeError(ControlStock $controlStock): string
    {
        if (is_null($controlStock->fecha_embalado)) {
            return 'Este producto aún no ha sido embalado y no puede despacharse';
        }

        if (!is_null($controlStock->fecha_salida)) {
            return 'Este producto ya ha sido despachado anteriormente';
        }

        return 'Este producto no cumple los criterios para ser despachado';
    }
}