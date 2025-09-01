<?php
// app/Http/Controllers/Admin/SectorAccesosController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SectorAcceso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class SectorAccesosController extends Controller
{
    public function index()
    {
        $sectores = SectorAcceso::orderBy('sector')->get();
        
        return Inertia::render('configuracion/SectorAccesos', [
            'sectores' => $sectores,
            'sectores_disponibles' => SectorAcceso::getSectoresDisponibles()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'sector' => 'required|string|in:prearmado,inyectado,armado,embalado,despacho|unique:sector_accesos,sector',
            'codigo' => 'required|string|min:3|max:20',
            'activo' => 'boolean'
        ], [
            'sector.required' => 'El sector es obligatorio',
            'sector.unique' => 'Ya existe un código para este sector',
            'codigo.required' => 'El código es obligatorio',
            'codigo.min' => 'El código debe tener al menos 3 caracteres',
            'codigo.max' => 'El código no puede tener más de 20 caracteres'
        ]);

        SectorAcceso::create([
            'sector' => $request->sector,
            'codigo_hash' => Hash::make($request->codigo),
            'activo' => $request->activo ?? true
        ]);

        return back()->with('success', 'Código de sector creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $sectorAcceso = SectorAcceso::findOrFail($id);
        
        $request->validate([
            'codigo' => 'required|string|min:3|max:20',
            'activo' => 'boolean'
        ], [
            'codigo.required' => 'El código es obligatorio',
            'codigo.min' => 'El código debe tener al menos 3 caracteres',
            'codigo.max' => 'El código no puede tener más de 20 caracteres'
        ]);

        $codigoExiste = SectorAcceso::get()->contains(function ($sector) use ($request) {
            return Hash::check($request->codigo, $sector->codigo_hash);
        });

        if ($codigoExiste) {
            return back()->withErrors(['codigo' => 'Ya existe este código en otro sector'])->withInput();
        }

        $sectorAcceso->update([
            'codigo_hash' => Hash::make($request->codigo),
            'activo' => $request->activo ?? true
        ]);

        return back()->with('success', 'Código de sector actualizado correctamente');
    }

    public function destroy($id)
    {
        $sectorAcceso = SectorAcceso::findOrFail($id);
        $sectorAcceso->delete();

        return back()->with('success', 'Código de sector eliminado correctamente');
    }

    public function toggleActivo($id)
    {
        $sectorAcceso = SectorAcceso::findOrFail($id);
        $sectorAcceso->update([
            'activo' => !$sectorAcceso->activo
        ]);

        $estado = $sectorAcceso->activo ? 'activado' : 'desactivado';
        return back()->with('success', "Sector {$estado} correctamente");
    }
}