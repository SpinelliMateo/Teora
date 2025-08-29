<?php

namespace App\Http\Controllers\Operarios\Auth;

use App\Http\Controllers\Controller;
use App\Models\SectorAcceso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
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

        $sectores = SectorAcceso::where('activo', true)->get();

        foreach ($sectores as $sectorAcceso) {

            if ($sectorAcceso->verificarCodigo($request->codigo)) {
     
                Session::put('operario_sector', $sectorAcceso->sector);
                Session::put('operario_auth', true);
                
                config(['session.lifetime' => 43200]); 
                
                return redirect()->route('sectores.operarios.' . $this->getRutaSector($sectorAcceso->sector));
            }
        }

        return back()->withErrors([
            'codigo' => 'Código incorrecto o inactivo'
        ])->withInput();
    }

    public function logout()
    {
        Session::forget(['operario_sector', 'operario_auth']);
        return redirect()->route('operarios.login');
    }

    private function getRutaSector(string $sector): string
    {
        return match($sector) {
            'prearmado' => 'sector.prearmado',
            'inyectado' => 'inyectado.index', // Necesitarás agregar el name a esta ruta
            'armado' => 'armado.index',
            'embalado' => 'embalado.index',
            'despacho' => 'despacho.index',
            default => 'sector.prearmado'
        };
    }
}