<?php

namespace App\Contracts;

use App\Models\ControlStock;

interface ControlStockRepositoryInterface
{
    public function findByNumeroSerie(string $numeroSerie): ?ControlStock;
    public function isValidForDespacho(ControlStock $controlStock): bool;
}