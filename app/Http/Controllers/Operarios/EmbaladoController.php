<?php

namespace App\Http\Controllers\Operarios;

use App\Http\Controllers\Controller;
use App\Services\EmbaladoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmbaladoController extends Controller
{
    private EmbaladoService $embaladoService;

    public function __construct(EmbaladoService $embaladoService)
    {
        $this->embaladoService = $embaladoService;
    }

    public function index(): Response
    {
        $estadisticas = $this->embaladoService->obtenerEstadisticasEmbaladoHoy();

        return Inertia::render('Operarios/Embalado/Index', [
            'estadisticas' => $estadisticas
        ]);
    }

    public function validarProductos(Request $request): JsonResponse
    {
        $request->validate([
            'numeros_serie' => 'required|array|min:1|max:4',
            'numeros_serie.*' => 'required|string'
        ]);

        $numerosSerie = array_filter($request->numeros_serie, function($serie) {
            return !empty(trim($serie));
        });

        if (empty($numerosSerie)) {
            return response()->json([
                'success' => false,
                'message' => 'Debe proporcionar al menos un número de serie válido.'
            ]);
        }

        $resultado = $this->embaladoService->validarProductosParaEmbalado($numerosSerie);

        // Agregar información de operarios para productos ya embalados
        if ($resultado['tiene_productos_ya_embalados']) {
            $productosYaEmbaladosConOperario = [];
            
            foreach ($resultado['productos_ya_embalados'] as $productoEmbalado) {
                // Obtener el operario que embaló este producto
                $operarioEmbalador = \DB::table('procesos_operarios')
                    ->join('operarios', 'procesos_operarios.operario_embalador_id', '=', 'operarios.id')
                    ->where('procesos_operarios.control_stock_id', $productoEmbalado['producto']->id)
                    ->select('operarios.nombre')
                    ->first();

                $productosYaEmbaladosConOperario[] = [
                    'n_serie' => $productoEmbalado['numero_serie'],
                    'fecha_embalado' => \Carbon\Carbon::parse($productoEmbalado['fecha_embalado'])->format('d/m/Y H:i'),
                    'operario' => $operarioEmbalador ? $operarioEmbalador->nombre : 'Sin operario'
                ];
            }
            
            $resultado['productos_ya_embalados'] = $productosYaEmbaladosConOperario;
        }

        return response()->json($resultado);
    }

    public function obtenerOperarios(): JsonResponse
    {
        $operarios = $this->embaladoService->obtenerOperariosEmbaladores();

        return response()->json(['operarios' => $operarios]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'numeros_serie' => 'required|array|min:1|max:4',
            'numeros_serie.*' => 'required|string',
            'operario_id' => 'required|exists:operarios,id',
            'continuar_con_embalados' => 'boolean'
        ]);

        $numerosSerie = array_filter($request->numeros_serie, function($serie) {
            return !empty(trim($serie));
        });

        if (empty($numerosSerie)) {
            return back()->withErrors(['message' => 'Debe proporcionar al menos un número de serie válido.']);
        }

        try {
            // Procesar embalado
            $resultado = $this->embaladoService->procesarEmbalado($numerosSerie, $request->operario_id);

            if (!$resultado['success']) {
                return back()->withErrors(['message' => $resultado['message']]);
            }

            // Preparar IDs para las etiquetas - SOLO productos recién embalados
            $controlStockIds = [];
            if (isset($resultado['data']['productos_embalados'])) {
                $controlStockIds = collect($resultado['data']['productos_embalados'])->pluck('id')->toArray();
            }

            // Si no hay productos para etiquetar, volver al index
            if (empty($controlStockIds)) {
                return redirect()->route('operarios.embalado.index')
                    ->with(['success' => true, 'message' => $resultado['message']]);
            }

            return redirect()->route('sectores.operarios.embalado.etiquetas', [
                'ids' => implode(',', $controlStockIds)
            ])->with(['success' => true, 'message' => $resultado['message']]);

        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Error al procesar el embalado: ' . $e->getMessage()]);
        }
    }

    /**
     * Vista para mostrar las etiquetas de productos embalados
     */
    public function etiquetas(Request $request): Response
    {
        $request->validate([
            'ids' => 'required|string'
        ]);

        $controlStockIds = array_filter(explode(',', $request->ids), 'is_numeric');
        
        if (empty($controlStockIds)) {
            return redirect()->route('sectores.operarios.embalado.index')
                ->withErrors(['message' => 'No se encontraron productos válidos para mostrar etiquetas.']);
        }

        $productos = $this->embaladoService->obtenerProductosEmbaladosParaEtiquetas($controlStockIds);

        return Inertia::render('Operarios/Embalado/Etiquetas', [
            'productos' => $productos,
            'fecha_proceso' => now()->format('d-m-Y'),
            'hora_proceso' => now()->format('H:i')
        ]);
    }

    /**
     * Reimprimir etiquetas de productos ya embalados
     */
    public function reimprimirEtiqueta(Request $request): RedirectResponse
    {
        $request->validate([
            'control_stock_ids' => 'required|array|min:1',
            'control_stock_ids.*' => 'required|integer|exists:control_stock,id'
        ]);

        try {
            // Verificar que todos los productos estén embalados
            $productos = $this->embaladoService->obtenerProductosEmbaladosParaEtiquetas($request->control_stock_ids);
            
            if ($productos->isEmpty()) {
                return back()->withErrors(['message' => 'No se encontraron productos embalados válidos para reimprimir.']);
            }

            return redirect()->route('sectores.operarios.embalado.etiquetas', [
                'ids' => implode(',', $request->control_stock_ids)
            ])->with(['success' => true, 'message' => 'Etiquetas listas para reimprimir.']);

        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Error al procesar la reimpresión: ' . $e->getMessage()]);
        }
    }
}