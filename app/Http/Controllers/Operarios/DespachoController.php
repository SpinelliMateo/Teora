<?php

namespace App\Http\Controllers\Operarios;

use App\Http\Controllers\Controller;
use App\Services\DespachoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class DespachoController extends Controller
{
    private DespachoService $despachoService;

    public function __construct(DespachoService $despachoService)
    {
        $this->despachoService = $despachoService;
    }

    public function index(): Response
    {
        $remitosADespachar = $this->despachoService->obtenerRemitosParaDespacho();

        return Inertia::render('Operarios/Despacho/Index', [
            'urls' => [
              'modelosRemitos'       => route('sectores.operarios.despacho.modelos-remitos'),
              'buscarControlStock'   => route('sectores.operarios.despacho.buscar-control-stock'),
            ],
            'remitos' => $remitosADespachar
        ]);
    }

    public function buscarControlStock(Request $request): JsonResponse
    {
        $request->validate([
            'numero_serie' => 'required|string'
        ]);

        $resultado = $this->despachoService->buscarControlStockPorSerie($request->numero_serie);

        return response()->json($resultado);
    }

    public function obtenerModelosRemitos(Request $request): JsonResponse
    {
        $request->validate([
            'remito_ids' => 'required|array',
            'remito_ids.*' => 'integer|exists:remitos,id'
        ]);

        $modelos = $this->despachoService->obtenerModelosAgrupados($request->remito_ids);

        return response()->json([
            'success' => true,
            'data' => $modelos
        ]);
    }
}