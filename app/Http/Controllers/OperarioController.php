<?php

namespace App\Http\Controllers;

use App\Models\Operario;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Picqer\Barcode\BarcodeGeneratorSVG;

class OperarioController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Operario::with('sectores');

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('apellido', 'like', "%{$search}%")
                      ->orWhere('n_legajo', 'like', "%{$search}%");
                });
            }

            $operarios = $query->orderBy('n_legajo', 'asc')->get();
            $sectores = Sector::where('activo', true)->orderBy('nombre')->get();

            return Inertia::render('configuracion/Operarios', [
                'operarios' => $operarios,
                'sectores' => $sectores,
                'legajo_sugerido' => Operario::sugerirLegajo()
            ]);
        } catch (\Exception $e) {
            Log::error('Error en index operarios: ' . $e->getMessage());
            return Inertia::render('configuracion/Operarios', [
                'operarios' => [],
                'sectores' => [],
                'legajo_sugerido' => Operario::sugerirLegajo()
            ]);
        }
    }

    public function store(Request $request)
    {
        Log::info('Datos recibidos en store:', $request->all());

        $validated = $request->validate([
            'operario_nombre' => 'required|string|max:255',
            'operario_apellido' => 'nullable|string|max:255',
            'operario_legajo' => 'required|string|max:50|unique:operarios,n_legajo',
            'operario_activo' => 'boolean',
            'sectores' => 'nullable|array',
            'sectores.*' => 'exists:sectores,id'
        ], [
            'operario_legajo.required' => 'El legajo es obligatorio',
            'operario_legajo.unique' => 'Este legajo ya existe'
        ]);

        DB::beginTransaction();
        
        try {
            $operario = Operario::create([
                'nombre' => $validated['operario_nombre'],
                'apellido' => $validated['operario_apellido'] ?? '',
                'n_legajo' => $validated['operario_legajo'],
                'activo' => $validated['operario_activo'] ?? true,
                'codigo_qr' => $this->generarCodigoQrUnico()
            ]);

            if (!empty($validated['sectores'])) {
                $sectoresValidos = array_filter($validated['sectores'], 'is_numeric');
                if (!empty($sectoresValidos)) {
                    $operario->sectores()->sync($sectoresValidos);
                }
            }

            DB::commit();

            Log::info('Operario creado exitosamente:', [
                'operario_id' => $operario->id,
                'sectores_asignados' => $validated['sectores'] ?? []
            ]);

            return redirect()->back()->with('success', 'Operario creado correctamente');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al crear operario: ' . $e->getMessage());
            
            return redirect()->back()->withErrors([
                'error' => 'Error al crear el operario: ' . $e->getMessage()
            ]);
        }
    }

    public function update(Request $request, Operario $operario)
    {
        Log::info('Datos recibidos en update:', [
            'operario_id' => $operario->id,
            'datos' => $request->all()
        ]);

        $validated = $request->validate([
            'operario_nombre' => 'required|string|max:255',
            'operario_apellido' => 'nullable|string|max:255',
            'operario_legajo' => 'required|string|max:50|unique:operarios,n_legajo,' . $operario->id,
            'operario_activo' => 'boolean',
            'sectores' => 'nullable|array',
            'sectores.*' => 'exists:sectores,id'
        ], [
            'operario_legajo.required' => 'El legajo es obligatorio',
            'operario_legajo.unique' => 'Este legajo ya existe'
        ]);

        DB::beginTransaction();

        try {
            $operario->update([
                'nombre' => $validated['operario_nombre'],
                'apellido' => $validated['operario_apellido'] ?? '',
                'n_legajo' => $validated['operario_legajo'],
                'activo' => $validated['operario_activo'] ?? true
            ]);

            if (isset($validated['sectores']) && is_array($validated['sectores'])) {
                $sectoresValidos = array_filter($validated['sectores'], 'is_numeric');
                $operario->sectores()->sync($sectoresValidos);
            } else {
                $operario->sectores()->detach();
            }

            DB::commit();

            Log::info('Operario actualizado exitosamente:', [
                'operario_id' => $operario->id,
                'sectores_asignados' => $validated['sectores'] ?? []
            ]);

            return redirect()->back()->with('success', 'Operario actualizado correctamente');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al actualizar operario: ' . $e->getMessage());
            
            return redirect()->back()->withErrors([
                'error' => 'Error al actualizar el operario: ' . $e->getMessage()
            ]);
        }
    }

    public function destroy(Operario $operario)
    {
        DB::beginTransaction();
        
        try {
            $operario->sectores()->detach();
            $operario->delete();
            
            DB::commit();
            
            Log::info('Operario eliminado exitosamente:', ['operario_id' => $operario->id]);
            
            return redirect()->back()->with('success', 'Operario eliminado correctamente');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al eliminar operario: ' . $e->getMessage());
            
            return redirect()->back()->withErrors([
                'error' => 'Error al eliminar el operario'
            ]);
        }
    }

    private function generarCodigoQrUnico()
    {
        do {
            $year = date('Y');
            $lastOperario = Operario::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
            
            $nextNumber = $lastOperario ? ($lastOperario->id + 1) : 1;
            $codigoQr = 'EMP' . $year . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            
            $exists = Operario::where('codigo_qr', $codigoQr)->exists();
            
        } while ($exists);

        return $codigoQr;
    }

    public function generarCodigoBarras(Operario $operario)
    {
        try {
            Log::info('Generando código de barras SVG para operario:', [
                'id' => $operario->id,
                'codigo_qr' => $operario->codigo_qr
            ]);
    
            $generator = new BarcodeGeneratorSVG();
            
            if (empty($operario->codigo_qr)) {
                Log::error('Código QR vacío para operario: ' . $operario->id);
                return response('<svg width="200" height="60"><text x="10" y="30" fill="red">Código QR vacío</text></svg>')
                    ->header('Content-Type', 'image/svg+xml');
            }
    
            $barcode = $generator->getBarcode(
                $operario->codigo_qr, 
                $generator::TYPE_CODE_128, 
                2, 
                50
            );
            
            Log::info('Código de barras SVG generado exitosamente');
            
            return response($barcode)
                ->header('Content-Type', 'image/svg+xml')
                ->header('Cache-Control', 'public, max-age=3600');
                
        } catch (\Exception $e) {
            Log::error('Error al generar código de barras: ' . $e->getMessage());
            
            return response('<svg width="200" height="60"><text x="10" y="30" fill="red">Error generando código</text></svg>')
                ->header('Content-Type', 'image/svg+xml');
        }
    }

    public function imprimirEtiqueta(Operario $operario)
    {
        try {

            // Construir el ZPL con datos reales
            $zpl = $this->construirTemplateZPL($operario);

            return response()->json([
                'success' => true,
                'message' => 'Etiqueta generada',
                'zpl' => $zpl
            ]);

        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Error al imprimir: ' . $e->getMessage()]);
        }

        return back()->with('message', "Etiqueta del operario {$operario->n_legajo} impresa.");
    }
    private function construirTemplateZPL(Operario $operario)
    {
        $nombre = strtoupper($operario->nombre . ' ' . $operario->apellido);
        $codigoqr = $operario->codigo_qr;

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


                ^FO145,50^BY3^BCN,110,N,N,N^FD{$codigoqr}^FS
                ^FO20,15^GB765,370,3^FS

                ^CF0,40,40
                ^FO260,245^FD{$nombre}^FS

                ^CF0,30,30
                ^FO310,180^FD{$codigoqr}^FS

                ^XZ
                ";

        return $zpl;
    }
}