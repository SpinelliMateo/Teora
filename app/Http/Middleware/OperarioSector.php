<?php
// app/Http/Middleware/OperarioSector.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class OperarioSector
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $sectorRequerido = null): Response
    {
        // Verificar si el operario está autenticado
        if (!Session::has('operario_auth') || !Session::has('operario_sector')) {
            return redirect()->route('operarios.login');
        }

        $sectorOperario = Session::get('operario_sector');


        // Si se especifica un sector requerido, verificar que coincida
        if ($sectorRequerido && $sectorOperario !== $sectorRequerido) {
            return response()->json([
                'error' => 'No posee autorización para acceder a este sector',
                'sector_actual' => $sectorOperario,
                'sector_requerido' => $sectorRequerido
            ], 403);
        }

        // Si no se especifica sector, detectarlo por la URL
        if (!$sectorRequerido) {
            $sectorDetectado = $this->detectarSectorPorUrl($request);
            
            if ($sectorDetectado && $sectorOperario !== $sectorDetectado) {
                // Si es una petición AJAX o API
                if ($request->wantsJson()) {
                    return response()->json([
                        'error' => 'No posee autorización para este sector',
                        'mensaje' => 'Su acceso está limitado al sector: ' . ucfirst($sectorOperario)
                    ], 403);
                }
                
                // Si es una petición web normal
                abort(403, 'No posee autorización para este sector. Su acceso está limitado al sector: ' . ucfirst($sectorOperario));
            }
        }

        return $next($request);
    }

    private function detectarSectorPorUrl(Request $request): ?string
    {
        $path = $request->path();
        
        if (str_contains($path, 'prearmado')) return 'prearmado';
        if (str_contains($path, 'inyectado')) return 'inyectado';
        if (str_contains($path, 'armado') && !str_contains($path, 'prearmado')) return 'armado';
        if (str_contains($path, 'embalado')) return 'embalado';
        if (str_contains($path, 'despacho')) return 'despacho';
        
        return null;
    }
}