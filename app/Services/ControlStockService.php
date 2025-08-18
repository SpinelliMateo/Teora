<?php

namespace App\Services;

use App\Contracts\ControlStockRepositoryInterface;
use App\Models\ControlStock;

class ControlStockService
{
    private ControlStockRepositoryInterface $controlStockRepository;

    public function __construct(ControlStockRepositoryInterface $controlStockRepository)
    {
        $this->controlStockRepository = $controlStockRepository;
    }

    public function crearControlStock(int $operarioId, int $modeloId, int $ordenFabricacionId): ControlStock
    {
        $numeroSerie = $this->controlStockRepository->generarNumeroSerie();
        
        $controlStock = $this->controlStockRepository->create([
            'modelo_id' => $modeloId,
            'orden_fabricacion_id' => $ordenFabricacionId,
            'n_serie' => $numeroSerie,
            'fecha_prearmado' => now(),
        ]);

        // Asociar el operario al proceso
        $controlStock->prearmadores()->attach($operarioId);

        return $controlStock;
    }

    public function obtenerConRelaciones(int $id): ?ControlStock
    {
        return $this->controlStockRepository->getByIdWithRelations($id);
    }
}