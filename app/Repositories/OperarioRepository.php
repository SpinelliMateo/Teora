<?php

namespace App\Repositories;

use App\Contracts\OperarioRepositoryInterface;
use App\Models\Operario;
use App\Models\Modelo;
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
        ->get();
    }

    public function getModelosPendientesPorOperario(int $operarioId): Collection
    {
        return Modelo::whereHas('ordenesFabricacion', function ($q) use ($operarioId) {
            $q->whereHas('operarios', function ($oq) use ($operarioId) {
                $oq->where('operarios.id', $operarioId);
            })
            ->whereDoesntHave('controlStock', function ($cs) {
                $cs->whereColumn(
                    'control_stock.orden_fabricacion_id',
                    'ordenes_fabricacion.id'
                )->whereColumn(
                    'control_stock.modelo_id',
                    'modelo_orden_fabricacion.modelo_id'
                );
            });
        })
        ->with(['ordenesFabricacion' => function ($q) use ($operarioId) {
            $q->whereHas('operarios', function ($oq) use ($operarioId) {
                $oq->where('operarios.id', $operarioId);
            });
        }])
        ->get()
        ->map(function ($modelo) {
            $orden = $modelo->ordenesFabricacion->first();
            return [
                'id' => $modelo->id,
                'nombre' => $modelo->nombre_modelo,
                'descripcion' => null,
                'codigo' => $modelo->modelo,
                'orden_fabricacion_id' => $orden ? $orden->id : null,
                'orden' => $orden ? ['id' => $orden->id, 'codigo' => $orden->id] : null
            ];
        });
    }
}