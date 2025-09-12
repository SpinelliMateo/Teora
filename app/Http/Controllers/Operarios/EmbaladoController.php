<?php

namespace App\Http\Controllers\Operarios;

use App\Http\Controllers\Controller;
use App\Services\EmbaladoService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
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

    public function validarSerie(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'serie' => 'required|string|exists:control_stock,n_serie'
        ]);

        if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'El número de serie ingresado no existe en el sistema.'
        ], 422);
    }

        $resultado = $this->embaladoService->validarSerieParaEmbalado($request->serie);

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

            return redirect()->route('sectores.operarios.embalado.etiquetas', [
                'ids' => implode(',', $controlStockIds)
            ])->with([
                'success' => true,
                'message' => $resultado['message']
            ]);


        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Error al procesar el embalado: ' . $e->getMessage()]);
        }
    }

    /**
     * Vista para mostrar las etiquetas de productos embalados
     */
    public function etiquetas(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|string'
        ]);

        $controlStockIds = array_filter(explode(',', $request->ids), 'is_numeric');

        $productos = $this->embaladoService->obtenerProductosEmbaladosParaEtiquetas($controlStockIds);
        return response()->json([
            'success' => true,
            'productos' => $productos,
            'fecha_proceso' => now()->format('d-m-Y'),
            'hora_proceso' => now()->format('H:i'),
        ]);

    }
    public function imprimirEtiqueta(Request $request): JsonResponse
    {
        $request->validate([
            'control_stock_ids' => 'required|array|min:1',
            'control_stock_ids.*' => 'required|integer|exists:control_stock,id'
        ]);

        try {
            $productos = $this->embaladoService->obtenerProductosEmbaladosParaEtiquetas($request->control_stock_ids);
            
            if ($productos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron productos embalados válidos para reimprimir.'
                ], 404);
            }

            $zpls = [];
            foreach ($productos as $controlStock) {
                $zpls[] = $this->construirTemplateZPL($controlStock);
            }

            return response()->json([
                'success' => true,
                'message' => 'Etiquetas generadas correctamente',
                'zpls' => $zpls, // Devolver array de ZPLs
                'cantidad' => count($zpls)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la impresión: ' . $e->getMessage()
            ], 500);
        }
    }
    private function construirTemplateZPL(array $controlStock)
    {
        $ingreso = \Carbon\Carbon::parse($controlStock['fecha_embalado'])->format('d-m-Y');
        $serie = $controlStock['n_serie'];
        $modelo = $controlStock['modelo']['modelo_nombre'];

        $zpl = "^XA
                ^PW807
                ^LH0,0
                ~DGR:SSGFX000.GRF,1311,23,:Z64:eJx1kzGLE0EYht/J5EiQJYmg2GyxXP7ApkuRIvcPrrn+YAstd6tL1INRggYJm/sBYRt/hRbHSg6uEW20CwaMuFZylyvCWfl93240E+PyMUzeDDPPvN87gPXt559nqzjKv+6WvIjbXENbVVnS55rYcj1Xk54tV9zoUsqxV3duPnItlSXvreanXLOm2UUSB5b8h8SxL5T0vnM9crcvlH/u8U7Z3vvv0bvl/6wWkimP/vQOTd8Cb7KhHHn30g0BsoLkiCf3RI5EbrHsXBuHPDG0t6LbDmQ0eDCnOl2li+fQbIKMBs2YiibBC0t+mFD1szPHTx1/6vhGRpw/MdIdN0odbkfKTQmVG0Emtqw715DuBHFbDxoHMYJRIxir1Se1+nx0MqNeNIdVE3uLYfV1DEoIT0ZB7LMtNfZMxyqjBhlUJkwSsiFiy8sEOqFFj/kwNuQdtymquFBkQTkU2VRJZp/GHeiOgbOkjYmBSdicvStgBeiZ+ECX9+TI6lfkZ/Nq8SQn+UYkFMWEAMSNZxxGf8wkBrUeAUhE1RbJce6N3iQpL4u9B0yimUQxybwgGWInieFfyiZpZYmnt0joyJCTLUeuSaK1jH88yd/JYtOTL8TQfFXImyQj6FER3cKTMybJc9LKc1Kn1pzf/qj1iigUgSAS8uKiHDpWTuodUE4+lJcUPSV+swUDJjmZP9VzkiWanjjDJE1W1Hq1JJZICKNfmcBPOSQtSsvU9Zikdvuz9AtRCjmSx25FDLlAiFbKryYy8nZ0GyjdvMcVyvR/40BG6uYh3ff+Iboo0f/evoyUQn6lJRp+A1T7T90=:76FC
                ^XA
                ^FO40,315
                ^XGR:SSGFX000.GRF,1,1^FS
                ~DGR:SSGFX000.GRF,611,13,:Z64:eJytksFKw0AQhqc1jYuE1uMigtuTFw8JXnJqCX2RiC/Q3iyIjlbqRfQNfAIPPsLGgN58BNnSQ4+2F4kYGmcnSOLBm8syyx+Gb//5N0VRLTO/DWl/cTUa7BJcY+RDclV/C10T8S+AZiiJd0uzt+USth6QAKOX3dGzhP22qmghGKLpUviZTwDdw44RMCikFZ+6nwm4K26Ilmg9DGVjr7giwAxj48mN/maTREqtnmyewoRoKRyhJ92TPKGvKRwjCHe8jlgEJJzVU5doKRwiSGeuFSqcQ0AOWrMsQEujNtkyuY+Wxm2mp7CktYWzdBXT6B6ivUZM65IDZ5VH1ttUGWtHJiSSSUyum2eNS/KWXJh+Rq6vp+VwnaXgeUicaxtiOamG2AYyWG9zVD6nI4dVbgfwWL3Czgf1a85agLfASlC6//GmdfE2Xtz/bFPU/6pvM3LBzw==:F4EA
                ^FO665,325
                ^XGR:SSGFX000.GRF,1,1^FS

                ^FO44,40^GB230,110,3^FS
                ^FO285,40^GB230,110,3^FS
                ^FO530,40^GB230,110,3^FS

                ^CF0,25,25
                ^FO54,50^FDING. A DEPOSITO^FS
                ^FO295,50^FDMODELO^FS
                ^FO540,50^FDN. SERIE^FS

                ^CF0,30,30
                ^FO54,85^FD{$ingreso}^FS
                ^FO295,85^FD{$modelo}^FS
                ^FO540,85^FD{$serie}^FS

                ^CF0,30,30
                ^FO360,290^FD{$serie}^FS

                ^FO245,180^BY3^BCN,90,N,N,N^FD{$serie}^FS
                ^FO20,15^GB765,370,3^FS
                ^XZ
                ";

        return $zpl;
        }
    }

