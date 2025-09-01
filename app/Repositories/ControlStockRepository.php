<?php

namespace App\Repositories;

use App\Contracts\ControlStockRepositoryInterface;
use App\Models\ControlStock;
use App\Models\Operario;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use League\Uri\Idna\Option;

class ControlStockRepository implements ControlStockRepositoryInterface
{
    // Métodos existentes para despacho
    public function findByNumeroSerie(string $numeroSerie): ?ControlStock
    {
        return ControlStock::with('modelo')
            ->where('n_serie', $numeroSerie)
            ->first();
    }

    public function findByNumeroMotor(string $numeroMotor): ?ControlStock
    {
        return ControlStock::with('modelo')
            ->where('equipo', $numeroMotor)
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

    public function create(array $data): ControlStock
    {
        return ControlStock::create($data);
    }
    
    public function getByIdWithRelations(int $id): ?ControlStock
    {
        return ControlStock::with([
            'modelo',
            'ordenFabricacion', 
            'prearmadores'
        ])->find($id);
    }
    
    public function generarNumeroSerie(): string
    {
        $ultimoNumero = ControlStock::max('n_serie') ?? 0;
        $nuevoNumero = $ultimoNumero + 1;
        return str_pad($nuevoNumero, 7, '0', STR_PAD_LEFT);
    }
}