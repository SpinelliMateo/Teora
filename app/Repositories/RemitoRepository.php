<?php

namespace App\Repositories;

use App\Contracts\RemitoRepositoryInterface;
use App\Models\ControlRemito;
use App\Models\ControlStock;
use App\Models\DespachoFinalizado;
use App\Models\DespachoRemito;
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

    public function updateEstado(array $remitoIds, string $estado): int
    {
        return Remito::whereIn('id', $remitoIds)->update([
            'estado' => $estado
        ]);
    }
    public function crearHistorial(array $remitoIds, array $controlStockIds): void
    {
        $ultimo_despacho = DespachoFinalizado::latest()->first();

        if ($ultimo_despacho) {
            $ultimo_numero = (int) str_replace('DESP-', '', $ultimo_despacho->numero_despacho);

            $nuevo_numero = $ultimo_numero + 1;

            $siguiente_numero = 'DESP-' . str_pad($nuevo_numero, 4, '0', STR_PAD_LEFT);
        }else {
            $siguiente_numero = 'DESP-0001';
        }
        $despacho = DespachoFinalizado::create([
            'numero_despacho' => $siguiente_numero ?? 'DESP-0001',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        foreach ($remitoIds as $remitoId) {
            DespachoRemito::create([
                'despacho_id' => $despacho->id,
                'remito_id' => $remitoId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        foreach ($controlStockIds as $controlStockId) {
            $serie = ControlStock::find($controlStockId);

            $series[] = $serie;
        }
        foreach ($remitoIds as $remitoId) {
            $remito = Remito::find($remitoId);

            foreach ($remito->modelos as $modelo) {
                // Cantidad que necesita este remito de este modelo
                $cantidadNecesaria = $modelo->pivot->cantidad;

                // Cantidad ya asignada en control_remito
                $asignadas = ControlRemito::where('remito_id', $remito->id)
                    ->whereHas('controlStock', function ($q) use ($modelo) {
                        $q->where('modelo_id', $modelo->id);
                    })
                    ->count();

                // Calcular cuántas faltan
                $faltan = $cantidadNecesaria - $asignadas;

                if ($faltan <= 0) {
                    continue; // ya está completo
                }

                // Buscar series disponibles y asignarlas
                foreach ($series as $key => $serie) {
                    if ($faltan === 0) {
                        break; // ya asignamos las necesarias
                    }

                    if ($serie->modelo_id === $modelo->id) {
                        ControlRemito::create([
                            'remito_id'        => $remito->id,
                            'control_stock_id' => $serie->id,
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ]);

                        unset($series[$key]);
                        $faltan--;
                    }
                }
            }
        }


    }
}