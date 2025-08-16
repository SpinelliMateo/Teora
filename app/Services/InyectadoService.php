<?php

namespace App\Services;

use App\Contracts\ControlStockRepositoryInterface;
use App\Models\ControlStock;
use Carbon\Carbon;

class InyectadoService
{
    private ControlStockRepositoryInterface $controlStockRepository;

    public function __construct(ControlStockRepositoryInterface $controlStockRepository)
    {
        $this->controlStockRepository = $controlStockRepository;
    }

    public function procesarInyectado(string $numeroSerie): array
    {
        $controlStock = $this->controlStockRepository->findByNumeroSerie($numeroSerie);

        if (!$controlStock) {
            return [
                'success' => false,
                'message' => '❌ No se encontró el registro con número de serie: ' . $numeroSerie
            ];
        }

        if (!$this->isValidForInyectado($controlStock)) {
            return [
                'success' => false,
                'message' => '⚠️ El producto con serie ' . $numeroSerie . ' no está en condiciones para ser inyectado.'
            ];
        }

        $this->marcarComoInyectado($controlStock);

        return [
            'success' => true,
            'message' => '✅ Producto N°' . $numeroSerie . ' marcado como INYECTADO exitosamente.'
        ];
    }

    private function isValidForInyectado(ControlStock $controlStock): bool
    {
        return !is_null($controlStock->fecha_prearmado) && is_null($controlStock->fecha_inyectado);
    }

    private function marcarComoInyectado(ControlStock $controlStock): void
    {
        $controlStock->update([
            'fecha_inyectado' => Carbon::now()
        ]);
    }
}