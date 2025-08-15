<?php

namespace App\Repositories;

use App\Contracts\OperarioRepositoryInterface;
use App\Models\Operario;
use Illuminate\Support\Collection;

class OperarioRepository implements OperarioRepositoryInterface
{
    public function getPrearmadoresConOrdenes(): Collection
    {
        return Operario::activos()
        ->whereHas('ordenesFabricacion', function ($q) {
            $q->whereHas('modelos', function ($mq) {
                $mq->whereDoesntHave('stock', function ($sq) {
                    $sq->whereColumn(
                        'control_stock.orden_fabricacion_id',
                        'modelo_orden_fabricacion.orden_fabricacion_id'
                    );
                });
            });
        })
        ->with(['ordenesFabricacion' => function ($q) {
            $q->with(['modelos' => function ($mq) {
                $mq->whereDoesntHave('stock', function ($sq) {
                    $sq->whereColumn(
                        'control_stock.orden_fabricacion_id',
                        'modelo_orden_fabricacion.orden_fabricacion_id'
                    );
                });
            }]);
        }])
        ->get();
    }
}