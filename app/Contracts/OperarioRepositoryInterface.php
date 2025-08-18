<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface OperarioRepositoryInterface
{
    public function getPrearmadoresConOrdenes(): Collection;
    
    public function getModelosPendientesPorOperario(int $operarioId): Collection;
    
    public function getOperariosArmadores(): Collection;

    public function getOperariosEmbaladores(): Collection;
}