<?php

namespace App\Http\Controllers;

use App\Models\ControlStock;
use App\Models\Operario;
use Illuminate\Http\Request;
use App\Models\ProcesosOperarios;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ProcesosOperariosController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $filtro = $request->input('filtro');
        // $fecha_desde = $request->input('fecha_desde');
        // $fecha_hasta = $request->input('fecha_hasta');
        
        $procesos_query = ProcesosOperarios::with(['control_stock.modelo', 'operario_armador', 'operario_prearmador', 'operario_embalador'])
        ->whereHas('control_stock', function ($query) {
            $query->whereNotNull('fecha_prearmado');
        });

        // if($fecha_desde && $fecha_hasta){
        //     $servicioQuery->where('created_at', '>=', Carbon::parse($fecha_desde)->startOfDay());
        //     $servicioQuery->where('created_at', '<=', Carbon::parse($fecha_hasta)->endOfDay());
        // }

        if($filtro == 'FINALIZADOS'){
            $procesos_query->whereHas('control_stock', function ($query) {
                $query->whereNotNull('fecha_salida');
            });
        }else{
            $procesos_query->whereHas('control_stock', function ($query) {
                $query->whereNull('fecha_salida');
            });
        }

        if (!empty($search)) {
            $procesos_query->where(function ($query) use ($search) {
                $query->whereHas('control_stock', function ($q) use ($search) {
                    $q->whereRaw('LOWER(n_serie) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(equipo) LIKE ?', ["%{$search}%"]);
                })
                ->orWhereHas('control_stock.modelo', function ($q) use ($search) {
                    $q->whereRaw('LOWER(modelo) LIKE ?', ["%{$search}%"]);
                })
                ->orWhereHas('operario_armador', function ($q) use ($search) {
                    $q->whereRaw('LOWER(nombre) LIKE ?', ["%{$search}%"]);
                })
                ->orWhereHas('operario_prearmador', function ($q) use ($search) {
                    $q->whereRaw('LOWER(nombre) LIKE ?', ["%{$search}%"]);
                })
                ->orWhereHas('operario_embalador', function ($q) use ($search) {
                    $q->whereRaw('LOWER(nombre) LIKE ?', ["%{$search}%"]);
                });
            }); 
        }

        $prearmadores = Operario::whereHas('sectores', function ($query) {
            $query->where('nombre', 'prearmado');
        })->orderBy('nombre', 'asc')->get();
        $armadores = Operario::whereHas('sectores', function ($query) {
            $query->where('nombre', 'armado');
        })->orderBy('nombre', 'asc')->get();
        $embaladores = Operario::whereHas('sectores', function ($query) {
            $query->where('nombre', 'embalado');
        })->orderBy('nombre', 'asc')->get();

        return inertia('procesos/Procesos', [
            'procesos' => $procesos_query->paginate(10),
            'filtro' => $filtro ?? 'EN PROCESO',
            'search' => $search,
            'prearmadores' => $prearmadores,
            'armadores' => $armadores,
            'embaladores' => $embaladores,
            'can' => [
                'ver' => auth()->user()->can('ver servicio proceso'),
                'gestionar' => auth()->user()->can('gestionar servicio proceso'),
            ] 
        ]);
    }

    public function update_proceso(Request $request){
        $validate = $request->validate([
            'id' => 'required',
            'fecha_prearmado' => 'nullable|string',
            'operario_prearmado' => 'nullable|integer|exists:operarios,id',
            'fecha_inyectado' => 'nullable|string',
            'fecha_armado' => 'nullable|string',
            'operario_armado' => 'nullable|integer|exists:operarios,id',
            'numero_motor' => 'nullable|string|max:50',
            'fecha_embalado' => 'nullable|string',
            'operario_embalado' => 'nullable|integer|exists:operarios,id',
        ]);
        // Validar jerarquía
        if (
            ($request->fecha_inyectado || $request->hora_inyectado) &&
            !$request->fecha_prearmado
        ) {
            return back()->withErrors([
                'message' => 'Debe ingresar el prearmado antes que el inyectado.'
            ]);
        }

        if (
            ($request->fecha_armado || $request->hora_armado) &&
            !$request->fecha_inyectado
        ) {
            return back()->withErrors([
                'message' => 'Debe ingresar el inyectado antes que el armado.'
            ]);
        }

        if (
            ($request->fecha_embalado || $request->hora_embalado) &&
            !$request->fecha_armado
        ) {
            return back()->withErrors([
                'message' => 'Debe ingresar el armado antes que el embalado.'
            ]);
        }

        try{
            $control_stock = ControlStock::findOrFail($request->id);

            $control_stock->update([
                'fecha_prearmado' => $request->fecha_prearmado,
                'fecha_inyectado' => $request->fecha_inyectado,
                'fecha_armado' => $request->fecha_armado,
                'fecha_embalado' => $request->fecha_embalado,
                'equipo' => $request->numero_motor,
            ]);
            
            // Actualizar operarios (incluyendo el caso de borrar/setear a null)
            if($request->has('operario_prearmado') || $request->has('operario_armado') || $request->has('operario_embalado')){
                $proceso = ProcesosOperarios::where('control_stock_id', $control_stock->id)->first();
                
                if($request->has('operario_prearmado')){
                    $proceso->operario_prearmador_id = $request->operario_prearmado;
                }
                if($request->has('operario_armado')){
                    $proceso->operario_armador_id = $request->operario_armado;
                }
                if($request->has('operario_embalado')){
                    $proceso->operario_embalador_id = $request->operario_embalado;
                }
                $proceso->save();
            }

            return back()->with('success', 'Proceso actualizado correctamente.');

        }catch (\Exception $e){
            Log::error($e->getMessage());
            return back()->withErrors([
                'message' => 'Ocurrio un error.'
            ]);
        }
    }
    public function imprimirEtiqueta(Request $request): JsonResponse
    {
        $request->validate([
            'control_stock_id' => 'required|integer|exists:control_stock,id',
            'tipo' => 'required|string|in:prearmado,embalado'
        ]);
        $controlStock = ControlStock::with('modelo')->find($request->control_stock_id);

        try {
            if ($request->tipo === 'prearmado') {
                $zpl = $this->construirTemplatePrearmadoZPL($controlStock);
            } elseif ($request->tipo === 'embalado') {
                $zpl = $this->construirTemplateEmbaladoZPL($controlStock);
            } else {
                return response()->json(['success' => false, 'message' => 'Tipo de impresión no válido.'], 400);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Etiqueta generada',
                'zpl' => $zpl
            ]);

        } catch (\Exception $e) {
            Log::error('Error en imprimirEtiqueta: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la impresion: ' . $e->getMessage()
            ], 500);
        }
        
    }
    private function construirTemplateEmbaladoZPL(ControlStock $controlStock)
    {
        $ingreso = \Carbon\Carbon::parse($controlStock->fecha_embalado)->format('d-m-Y');
        $serie = $controlStock->n_serie;
        $modelo = $controlStock->modelo->nombre_modelo;

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
    private function construirTemplatePrearmadoZPL(ControlStock $controlStock)
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
