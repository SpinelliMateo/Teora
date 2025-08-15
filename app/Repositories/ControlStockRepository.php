<?php

namespace App\Repositories;

use App\Contracts\ControlStockRepositoryInterface;
use App\Models\ControlStock;
use Illuminate\Support\Collection;
use Carbon\Carbon;

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
        return !is_null($controlStock->fecha_embalado) && is_null($controlStock->fecha_salida);
    }

    public function getByIds(array $ids): Collection
    {
        return ControlStock::with('modelo')->whereIn('id', $ids)->get();
    }

    public function updateFechaSalida(array $ids, Carbon $fecha): int
    {
        return ControlStock::whereIn('id', $ids)->update([
            'fecha_salida' => $fecha
        ]);
    }
}