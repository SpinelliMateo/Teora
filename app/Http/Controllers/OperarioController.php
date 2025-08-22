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

    public function mostrarEtiqueta(Operario $operario)
    {
        try {
            return Inertia::render('configuracion/EtiquetaOperario', [
                'operario' => $operario->load('sectores')
            ]);
        } catch (\Exception $e) {
            Log::error('Error al mostrar etiqueta: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Error al cargar la etiqueta']);
        }
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
}