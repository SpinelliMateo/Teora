<?php

namespace App\Contracts;

use App\Models\ControlStock;
use App\Models\Operario;
use Illuminate\Support\Collection;
use Carbon\Carbon;

interface ControlStockRepositoryInterface
{
    public function findByNumeroSerie(string $numeroSerie): ?ControlStock;
    public function findByNumeroMotor(string $numeroMotor): ?ControlStock;
    public function isValidForDespacho(ControlStock $controlStock): bool;
    public function getByIds(array $ids): Collection;
    public function updateFechaSalida(array $ids, Carbon $fecha): int;
    public function create(array $data): ControlStock;
    public function getByIdWithRelations(int $id): ?ControlStock;
    public function generarNumeroSerie(): string;
}