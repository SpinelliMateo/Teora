<?php

namespace App\Http\Controllers\Operarios;

use App\Http\Controllers\Controller;
use App\Http\Requests\InyectadoRequest;
use App\Services\InyectadoService;
use Inertia\Inertia;

class InyectadoController extends Controller
{
    public function __construct(private InyectadoService $inyectadoService) {}

    public function index()
    {
        return Inertia::render('Operarios/Inyectado/Index');
    }

    public function store(InyectadoRequest $request)
    {
       
        $data = $request->validated();
        $numeroSerie = $data['numero_serie'];

        $resultado = $this->inyectadoService->procesarInyectado($numeroSerie);

        return back()->with($resultado);
    }
}
