<?php

namespace App\Http\Controllers\Operarios\Auth;

use App\Http\Controllers\Controller;
use App\Models\SectorAcceso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class OperarioAuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Operarios/Login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|min:3'
        ], [
            'codigo.required' => 'El código es obligatorio',
            'codigo.min' => 'El código debe tener al menos 3 caracteres'
        ]);

        // Limpiar cache para evitar datos obsoletos
        Cache::flush();
        
        // Limpiar cualquier sesión anterior
        Session::forget(['operario_sector', 'operario_auth']);

        // Obtener sectores activos sin cache
        $sectores = SectorAcceso::where('activo', true)
            ->orderBy('updated_at', 'desc') // Obtener los más recientemente actualizados primero
            ->get();

        // Log para debug
        Log::info('Intentando login con código: ' . $request->codigo);
        Log::info('Sectores encontrados: ', $sectores->pluck('sector', 'id')->toArray());

        $codigoValido = false;
        $sectorEncontrado = null;

        foreach ($sectores as $sectorAcceso) {
            // Log del intento de verificación
            Log::info("Verificando sector: {$sectorAcceso->sector} con código proporcionado");
            
            if ($sectorAcceso->verificarCodigo($request->codigo)) {
                $codigoValido = true;
                $sectorEncontrado = $sectorAcceso;
                
                Log::info("Código válido encontrado para sector: {$sectorAcceso->sector}");
                break; // Salir del loop una vez encontrado
            }
        }

        if ($codigoValido && $sectorEncontrado) {
            // Establecer sesión
            Session::put('operario_sector', $sectorEncontrado->sector);
            Session::put('operario_auth', true);
            Session::put('operario_sector_id', $sectorEncontrado->id); // Para mayor control
            
            // Configurar tiempo de sesión (30 días en minutos)
            config(['session.lifetime' => 43200]); 
            
            Log::info("Login exitoso para sector: {$sectorEncontrado->sector}");
            
            return redirect()->route('sectores.operarios.' . $this->getRutaSector($sectorEncontrado->sector));
        }

        Log::warning("Intento de login fallido con código: {$request->codigo}");
        
        return back()->withErrors([
            'codigo' => 'Código incorrecto o inactivo'
        ])->withInput();
    }

    public function logout()
    {
        Log::info('Cerrando sesión de operario');
        
        // Limpiar completamente la sesión
        Session::flush(); // Esto limpia toda la sesión
        
        return redirect()->route('sectores.operarios.login');
    }

    private function getRutaSector(string $sector): string
    {
        return match($sector) {
            'prearmado' => 'sector.prearmado',
            'inyectado' => 'inyectado.index',
            'armado' => 'armado.index',
            'embalado' => 'embalado.index',
            'despacho' => 'despacho.index',
            default => 'sector.prearmado'
        };
    }

    /**
     * Método para verificar el estado actual de la sesión (útil para debug)
     */
    public function checkSession()
    {
        return response()->json([
            'operario_auth' => Session::get('operario_auth'),
            'operario_sector' => Session::get('operario_sector'),
            'operario_sector_id' => Session::get('operario_sector_id'),
            'session_id' => Session::getId()
        ]);
    }
}