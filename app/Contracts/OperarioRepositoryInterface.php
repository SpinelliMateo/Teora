<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface OperarioRepositoryInterface
{
    public function getPrearmadoresConOrdenes(): Collection;
}