<?php

namespace App\Http\Controllers\Operarios;

use App\Http\Controllers\Controller;
use App\Services\PrearmadoService;
use Inertia\Inertia;
use Inertia\Response;

class PrearmadoController extends Controller
{
    private PrearmadoService $prearmadoService;

    public function __construct(PrearmadoService $prearmadoService)
    {
        $this->prearmadoService = $prearmadoService;
    }

    public function index(): Response
    {
        $prearmadores = $this->prearmadoService->obtenerPrearmadoresConOrdenes();

        dd($prearmadores); 

        return Inertia::render('Operarios/Prearmado/Index', [
            'prearmadores' => $prearmadores
        ]);
    }
}