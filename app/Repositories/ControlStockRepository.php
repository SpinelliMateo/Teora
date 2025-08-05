<?php

namespace App\Repositories;

use App\Contracts\ControlStockRepositoryInterface;
use App\Models\ControlStock;

class ControlStockRepository implements ControlStockRepositoryInterface
{
    public function findByNumeroSerie(string $numeroSerie): ?ControlStock
    {
        return ControlStock::with('modelo')
            ->where('n_serie', $numeroSerie)
            ->first();
    }

    public function isValidForDespacho(ControlStock $controlStock): bool
    {
        // Debe estar embalado (fecha_embalado no null) y no despachado (fecha_salida null)
        return !is_null($controlStock->fecha_embalado) && is_null($controlStock->fecha_salida);
    }
}