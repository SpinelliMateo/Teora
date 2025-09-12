<?php

namespace App\Http\Controllers\Operarios;

use App\Http\Controllers\Controller;
use App\Models\ControlStock;
use App\Services\PrearmadoService;
use App\Services\ControlStockService;
use App\Models\Operario;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

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

    public function store(Request $request)
    {
        $request->validate([
            'operario_id' => 'required|exists:operarios,id',
            'modelos' => 'required|array|min:1',
            'modelos.*' => 'exists:modelos,id'
        ]);

        try {
            $modeloId = $request->modelos[0];
            $modelos = $this->prearmadoService->obtenerModelosPendientes($request->operario_id);
            $modeloData = $modelos->firstWhere('id', $modeloId);

            if (!$modeloData || !$modeloData['orden_fabricacion_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Modelo o orden de fabricación no encontrada.'
                ], 404);
            }

            $controlStock = $this->controlStockService->crearControlStock(
                $request->operario_id,
                $modeloId,
                $modeloData['orden_fabricacion_id']
            );

            if (!$controlStock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Control de stock no encontrado'
                ], 404);
            }

            $zpl = $this->construirTemplateZPL($controlStock);

            return response()->json([
                'success' => true,
                'message' => 'Etiqueta generada',
                'zpl' => $zpl
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el prearmado: ' . $e->getMessage()
            ], 500);
        }
    }

    private function construirTemplateZPL(ControlStock $controlStock)
    {
        $fecha = \Carbon\Carbon::parse($controlStock->fecha_prearmado)->format('d-m-Y');
        $serie = $controlStock->n_serie;
        $modelo = $controlStock->modelo->nombre_modelo;
        $tension = $controlStock->modelo->tension;
        $frecuencia = $controlStock->modelo->frecuencia;
        $corriente = $controlStock->modelo->corriente;
        $aislacion = $controlStock->modelo->aislacion;
        $sistema = $controlStock->modelo->sistema;
        $volumen = $controlStock->modelo->volumen;
        $espumante = $controlStock->modelo->espumante;
        $clase = $controlStock->modelo->clase;
        $gas = $controlStock->modelo->gas;
        $cantidad_gas = $controlStock->modelo->cantidad_gas;
        $codigo_modelo = $controlStock->modelo->modelo ?? $controlStock->modelo->codigo_modelo ?? $modelo;

        $zpl = "^XA
                ^PW807
                ^LH0,0


                ~DGR:SSGFX000.GRF,1311,23,:Z64:eJx1kzGLE0EYht/J5EiQJYmg2GyxXP7ApkuRIvcPrrn+YAstd6tL1INRggYJm/sBYRt/hRbHSg6uEW20CwaMuFZylyvCWfl93240E+PyMUzeDDPPvN87gPXt559nqzjKv+6WvIjbXENbVVnS55rYcj1Xk54tV9zoUsqxV3duPnItlSXvreanXLOm2UUSB5b8h8SxL5T0vnM9crcvlH/u8U7Z3vvv0bvl/6wWkimP/vQOTd8Cb7KhHHn30g0BsoLkiCf3RI5EbrHsXBuHPDG0t6LbDmQ0eDCnOl2li+fQbIKMBs2YiibBC0t+mFD1szPHTx1/6vhGRpw/MdIdN0odbkfKTQmVG0Emtqw715DuBHFbDxoHMYJRIxir1Se1+nx0MqNeNIdVE3uLYfV1DEoIT0ZB7LMtNfZMxyqjBhlUJkwSsiFiy8sEOqFFj/kwNuQdtymquFBkQTkU2VRJZp/GHeiOgbOkjYmBSdicvStgBeiZ+ECX9+TI6lfkZ/Nq8SQn+UYkFMWEAMSNZxxGf8wkBrUeAUhE1RbJce6N3iQpL4u9B0yimUQxybwgGWInieFfyiZpZYmnt0joyJCTLUeuSaK1jH88yd/JYtOTL8TQfFXImyQj6FER3cKTMybJc9LKc1Kn1pzf/qj1iigUgSAS8uKiHDpWTuodUE4+lJcUPSV+swUDJjmZP9VzkiWanjjDJE1W1Hq1JJZICKNfmcBPOSQtSsvU9Zikdvuz9AtRCjmSx25FDLlAiFbKryYy8nZ0GyjdvMcVyvR/40BG6uYh3ff+Iboo0f/evoyUQn6lJRp+A1T7T90=:76FC
                ^XA
                ^FO46,26
                ^XGR:SSGFX000.GRF,1,1^FS

                ^CF0,28,28
                ^FO250,30^FDFabrica, distribuye y garant. J. A. Stefanelli^FS
                ^CF0,24,24
                ^FO250,65^FDAvda. H. Yrigoyen 5672 Ezpeleta (Bs. As.)^FS

                ^FO44,100^GB720,3,3^FS

                ^FO44,120^GB220,90,3^FS
                ^FO295,120^GB220,90,3^FS
                ^FO543,120^GB220,90,3^FS

                ^CF0,25,25
                ^FO64,135^FDFECHA^FS
                ^FO315,135^FDSERIE^FS
                ^FO563,135^FDMODELO^FS

                ^CF0,30,30
                ^FO64,160^FD{$fecha}^FS
                ^FO315,160^FD{$serie}^FS
                ^FO563,160^FD{$modelo}^FS

                ^FO44,220^GB170,85,3^FS
                ^FO227,220^GB170,85,3^FS
                ^FO410,220^GB170,85,3^FS
                ^FO592,220^GB170,85,3^FS

                ^CF0,25,25
                ^FO54,230^FDTENSION^FS
                ^FO237,230^FDFRECUENCIA^FS
                ^FO420,230^FDCORRIENTE^FS
                ^FO602,230^FDAISLACION^FS

                ^CF0,30,30
                ^FO54,255^FD{$tension}^FS
                ^FO237,255^FD{$frecuencia}^FS
                ^FO420,255^FD{$corriente}^FS
                ^FO602,255^FD{$aislacion}^FS

                ^FO44,325^GB170,85,3^FS
                ^FO227,325^GB170,85,3^FS
                ^FO410,325^GB170,85,3^FS
                ^FO592,325^GB170,85,3^FS

                ^CF0,25,25
                ^FO54,335^FDSISTEMA^FS
                ^FO237,335^FDVOL. BRUTO^FS
                ^FO420,335^FDAG. ESPUM.^FS
                ^FO602,335^FDCLASE CLIM.^FS

                ^CF0,30,30
                ^FO54,360^FD{$sistema}^FS
                ^FO237,360^FD{$volumen}^FS
                ^FO420,360^FD{$espumante}^FS
                ^FO602,360^FD{$clase}^FS

                ^FO44,430^GB350,100,3^FS
                ^FO413,430^GB350,100,3^FS

                ^CF0,25,25
                ^FO64,445^FDREFRIGERANTE^FS
                ^FO433,445^FDCANTIDAD^FS

                ^CF0,30,30
                ^FO64,475^FD{$gas}^FS
                ^FO433,475^FD{$cantidad_gas}^FS

                ^FO44,550^GB350,170,3^FS
                ^FO413,550^GB350,170,3^FS

                ^CF0,25,25
                ^FO64,560^FDSERIE^FS
                ^FO433,560^FDMODELO^FS

                ^FO64,590^BY2^BCN,80,N,N,N^FD{$serie}^FS
                ^FO433,590^BY2^BCN,80,N,N,N^FD{$codigo_modelo}^FS

                ^CF0,25,25
                ^FO64,685^FD{$serie}^FS
                ^FO433,685^FD{$codigo_modelo}^FS

                ^FO44,730^GB720,3,3^FS

                ~DGR:SSGFX000.GRF,611,13,:Z64:eJytksFKw0AQhqc1jYuE1uMigtuTFw8JXnJqCX2RiC/Q3iyIjlbqRfQNfAIPPsLGgN58BNnSQ4+2F4kYGmcnSOLBm8syyx+Gb//5N0VRLTO/DWl/cTUa7BJcY+RDclV/C10T8S+AZiiJd0uzt+USth6QAKOX3dGzhP22qmghGKLpUviZTwDdw44RMCikFZ+6nwm4K26Ilmg9DGVjr7giwAxj48mN/maTREqtnmyewoRoKRyhJ92TPKGvKRwjCHe8jlgEJJzVU5doKRwiSGeuFSqcQ0AOWrMsQEujNtkyuY+Wxm2mp7CktYWzdBXT6B6ivUZM65IDZ5VH1ttUGWtHJiSSSUyum2eNS/KWXJh+Rq6vp+VwnaXgeUicaxtiOamG2AYyWG9zVD6nI4dVbgfwWL3Czgf1a85agLfASlC6//GmdfE2Xtz/bFPU/6pvM3LBzw==:F4EA
                ^FO44,741
                ^XGR:SSGFX000.GRF,1,1^FS

                ^CF0,20,20
                ^FO620,745^FDOrigen: Argentina^FS
                ^CF0,18,18
                ^FO650,768^FD258765 234^FS

                ^XZ
                ";

        return $zpl;
    }
}