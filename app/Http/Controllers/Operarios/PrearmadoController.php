<?php

namespace App\Http\Controllers\Operarios;

use App\Http\Controllers\Controller;
use App\Services\PrearmadoService;
use App\Services\ControlStockService;
use App\Models\Operario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class PrearmadoController extends Controller
{
    private PrearmadoService $prearmadoService;
    private ControlStockService $controlStockService;

    public function __construct(
        PrearmadoService $prearmadoService,
        ControlStockService $controlStockService
    ) {
        $this->prearmadoService = $prearmadoService;
        $this->controlStockService = $controlStockService;
    }

    public function index(): Response
    {
        $prearmadores = $this->prearmadoService->obtenerPrearmadoresConOrdenes();

        return Inertia::render('Operarios/Prearmado/Index', [
            'prearmadores' => $prearmadores
        ]);
    }

    public function obtenerModelosPendientes(Operario $operario): JsonResponse
    {
        $modelos = $this->prearmadoService->obtenerModelosPendientes($operario->id);

        return response()->json(['modelos' => $modelos]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'operario_id' => 'required|exists:operarios,id',
            'modelos' => 'required|array|min:1',
            'modelos.*' => 'exists:modelos,id'
        ]);

        try {
            // Obtener el primer modelo y su orden de fabricación
            $modeloId = $request->modelos[0];
            $modelos = $this->prearmadoService->obtenerModelosPendientes($request->operario_id);
            $modeloData = $modelos->firstWhere('id', $modeloId);
            
            if (!$modeloData || !$modeloData['orden_fabricacion_id']) {
                return back()->withErrors(['message' => 'Modelo o orden de fabricación no encontrada.']);
            }

            $controlStock = $this->controlStockService->crearControlStock(
                $request->operario_id,
                $modeloId,
                $modeloData['orden_fabricacion_id']
            );

            return redirect()->route('sectores.operarios.prearmado.detalle', $controlStock->id);

        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Error al crear el prearmado: ' . $e->getMessage()]);
        }
    }

    public function detalle(int $id): Response
    {
        $controlStock = $this->controlStockService->obtenerConRelaciones($id);
        
        if (!$controlStock) {
            abort(404, 'Control de stock no encontrado');
        }

        return Inertia::render('Operarios/Prearmado/Detalle', [
            'controlStock' => $controlStock
        ]);
    }

    public function etiqueta(int $id): Response
    {
        $controlStock = $this->controlStockService->obtenerConRelaciones($id);
        
        if (!$controlStock) {
            abort(404, 'Control de stock no encontrado');
        }

        return Inertia::render('Operarios/Prearmado/Etiqueta', [
            'controlStock' => $controlStock
        ]);
    }
}