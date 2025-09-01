<?php

namespace App\Repositories;

use App\Contracts\OperarioRepositoryInterface;
use App\Models\ControlStock;
use App\Models\Operario;
use App\Models\Modelo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperarioRepository implements OperarioRepositoryInterface
{
    public function getPrearmadoresConOrdenes(): Collection 
    {
        return Operario::activos()
            ->whereHas('sectores', fn($s) => $s->where('nombre', 'Prearmado'))
            ->whereHas('ordenesFabricacion', function ($q) {
                $q->whereHas('modelos', function ($mq) {
                    $mq->whereRaw('
                        (SELECT cantidad FROM modelo_orden_fabricacion 
                         WHERE modelo_orden_fabricacion.orden_fabricacion_id = ordenes_fabricacion.id 
                         AND modelo_orden_fabricacion.modelo_id = modelos.id) > 
                        (SELECT COUNT(*) FROM control_stock 
                         WHERE control_stock.orden_fabricacion_id = ordenes_fabricacion.id 
                         AND control_stock.modelo_id = modelos.id)
                    ');
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
            ->whereRaw('
                (SELECT cantidad FROM modelo_orden_fabricacion 
                 WHERE modelo_orden_fabricacion.orden_fabricacion_id = ordenes_fabricacion.id 
                 AND modelo_orden_fabricacion.modelo_id = modelos.id) > 
                (SELECT COUNT(*) FROM control_stock 
                 WHERE control_stock.orden_fabricacion_id = ordenes_fabricacion.id 
                 AND control_stock.modelo_id = modelos.id)
            ');
        })
        ->with(['ordenesFabricacion' => function ($q) use ($operarioId) {
            $q->whereHas('operarios', function ($oq) use ($operarioId) {
                $oq->where('operarios.id', $operarioId);
            });
        }])
        ->get()
        ->map(function ($modelo) {
            $orden = $modelo->ordenesFabricacion->first();
            
            $pivot = $orden->pivot ?? null;
            $cantidadTotal = $pivot ? $pivot->cantidad : 0;
            $cantidadCompletada = ControlStock::where('orden_fabricacion_id', $orden->id)
                ->where('modelo_id', $modelo->id)
                ->count();
            
            return [
                'id' => $modelo->id,
                'nombre' => $modelo->nombre_modelo,
                'descripcion' => null,
                'codigo' => $modelo->modelo,
                'orden_fabricacion_id' => $orden ? $orden->id : null,
                'orden' => $orden ? ['id' => $orden->id, 'codigo' => $orden->id] : null,
                'cantidad_total' => $cantidadTotal,
                'cantidad_completada' => $cantidadCompletada,
                'cantidad_pendiente' => $cantidadTotal - $cantidadCompletada
            ];
        });
    }

    public function crearPrearmado($ordenFabricacionId, $modeloId, $operarioId, $numeroSerie = null)
    {
        
        $cantidadTotal = DB::table('modelo_orden_fabricacion')
            ->where('orden_fabricacion_id', $ordenFabricacionId)
            ->where('modelo_id', $modeloId)
            ->value('cantidad');
        
        $cantidadCompletada = ControlStock::where('orden_fabricacion_id', $ordenFabricacionId)
            ->where('modelo_id', $modeloId)
            ->count();
        
        if ($cantidadCompletada >= $cantidadTotal) {
            throw new \Exception('Ya se completaron todas las unidades de este modelo');
        }
        
        if (!$numeroSerie) {
            $numeroSerie = $this->generarNumeroSerie($ordenFabricacionId, $modeloId, $cantidadCompletada + 1);
        }
        
        return ControlStock::insert([
            'orden_fabricacion_id' => $ordenFabricacionId,
            'modelo_id' => $modeloId,
            'n_serie' => $numeroSerie,
            'fecha_prearmado' => now(),
            'prearmador' => $operarioId,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    private function generarNumeroSerie($ordenId, $modeloId, $secuencial)
    {
        return sprintf('ORD%03d-MOD%03d-%03d', $ordenId, $modeloId, $secuencial);
    }

    public function getOperariosArmadores(): Collection
    {
        return Operario::activos()
             ->whereHas('sectores', fn($q) => $q->where('nombre', 'Armado'))
            ->orderBy('nombre')
            ->get();
    }

    public function getOperariosEmbaladores(): Collection
    {
        return Operario::activos()
            ->whereHas('sectores', fn($q) => $q->where('nombre', 'Embalado'))
            ->orderBy('nombre')
            ->get();
    }

    
}