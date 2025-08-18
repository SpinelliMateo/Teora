<?php

namespace App\Http\Controllers\Operarios;

use App\Http\Controllers\Controller;
use App\Services\ArmadoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class ArmadoController extends Controller
{
    private ArmadoService $armadoService;

    public function __construct(ArmadoService $armadoService)
    {
        $this->armadoService = $armadoService;
    }

    public function index(): Response
    {
        $estadisticas = $this->armadoService->obtenerEstadisticasArmadoHoy();

        return Inertia::render('Operarios/Armado/Index', [
            'estadisticas' => $estadisticas
        ]);
    }

    public function validarProducto(Request $request): JsonResponse
    {
        $request->validate([
            'numero_serie' => 'required|string'
        ]);

        $resultado = $this->armadoService->validarProductoParaArmado($request->numero_serie);

        return response()->json($resultado);
    }

    public function obtenerOperarios(): JsonResponse
    {
        $operarios = $this->armadoService->obtenerOperariosArmadores();

        return response()->json(['operarios' => $operarios]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'numero_serie' => 'required|string',
            'numero_motor' => 'required|string|unique:control_stock,equipo',
            'operario_id' => 'required|exists:operarios,id'
        ]);

        try {
            $resultado = $this->armadoService->procesarArmado(
                $request->numero_serie,
                $request->numero_motor,
                $request->operario_id
            );

            if (!$resultado['success']) {
                return back()->withErrors(['message' => $resultado['message']]);
            }

            return back()->with(['success' => true, 'message' => $resultado['message']]);

        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Error al procesar el armado: ' . $e->getMessage()]);
        }
    }
}