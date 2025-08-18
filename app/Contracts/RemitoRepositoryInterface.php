<?php
namespace App\Contracts;

use Illuminate\Support\Collection;

interface RemitoRepositoryInterface
{
    public function getByEstado(string $estado): Collection;
    public function getModelosPorRemito(array $remitoIds): Collection; 
    public function updateEstado(array $remitoIds, string $estado): int;
}