<?php

namespace App\Http\Controllers;

use App\Models\DespachoFinalizado;
use App\Models\Modelo;
use App\Models\Remito;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HistorialController extends Controller
{
    public function index(Request $request)
    {

        $search = $request->input('search');
        $fecha_desde = $request->input('fecha_desde');
        $fecha_hasta = $request->input('fecha_hasta');
        $cliente = $request->input('cliente');

        $despachosQuery = DespachoFinalizado::with('remitos.controlStock.modelo');

        if ($fecha_desde) {
            $despachosQuery->where('created_at', '>=', Carbon::parse($fecha_desde)->startOfDay());
        }
        if ($fecha_hasta) {
            $despachosQuery->where('created_at', '<=', Carbon::parse($fecha_hasta)->endOfDay());
        }
        if (!empty($cliente)) {
            $despachosQuery->whereHas('remitos', function ($query) use ($cliente) {
            $query->where('cliente', $cliente);
            });
        }

        if (!empty($search)) {
            $search = strtolower($search);

            $despachosQuery->where(function ($query) use ($search) {
                // Buscar por numero_despacho (de la tabla despachos_finalizados)
                $query->whereRaw('LOWER(numero_despacho) LIKE ?', ["%{$search}%"])
                
                    // Buscar en remitos relacionados
                    ->orWhereHas('remitos', function ($q) use ($search) {
                        $q->whereRaw('LOWER(n_remito) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(cliente) LIKE ?', ["%{$search}%"]);
                    });
            });
        }

        $despachos = $despachosQuery->latest()->get();
        $clientes = Remito::select('cliente')->distinct()->pluck('cliente');
        return inertia('despachos/Despachos', [
            'despachos' => $despachos,
            'clientes' => $clientes,
        ]);
    }
}
