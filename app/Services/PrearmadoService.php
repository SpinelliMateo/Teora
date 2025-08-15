<?php

namespace App\Services;

use App\Contracts\OperarioRepositoryInterface;
use Illuminate\Support\Collection;

class PrearmadoService
{
    private OperarioRepositoryInterface $operarioRepository;

    public function __construct(OperarioRepositoryInterface $operarioRepository)
    {
        $this->operarioRepository = $operarioRepository;
    }

    public function obtenerPrearmadoresConOrdenes(): Collection
    {
        return $this->operarioRepository->getPrearmadoresConOrdenes();
    }
}