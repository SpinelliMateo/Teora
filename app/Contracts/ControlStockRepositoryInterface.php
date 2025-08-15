<?php

namespace App\Contracts;

use App\Models\ControlStock;
use Illuminate\Support\Collection;
use Carbon\Carbon;

interface ControlStockRepositoryInterface
{
    public function findByNumeroSerie(string $numeroSerie): ?ControlStock;
    public function isValidForDespacho(ControlStock $controlStock): bool;
    public function getByIds(array $ids): Collection;
    public function updateFechaSalida(array $ids, Carbon $fecha): int;
}