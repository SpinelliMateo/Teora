<?php

namespace App\Repositories;

use App\Contracts\RemitoRepositoryInterface;
use App\Models\Remito;
use Illuminate\Support\Collection;

class RemitoRepository implements RemitoRepositoryInterface
{
    public function getByEstado(string $estado): Collection
    {
        return Remito::with(['modelos'=>function($query){
            $query->withPivot('cantidad');
        }])
        ->where('estado', $estado)
        ->get();
    }

    public function getModelosPorRemito(array $remitoIds): Collection
    {
        $remitos = Remito::with('modelos')
        ->whereIn('id', $remitoIds)
        ->get();

        return $remitos->flatMap(fn(Remito $remito) => $remito->modelos);
    }
}