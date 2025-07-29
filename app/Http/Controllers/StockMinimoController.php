<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockMinimo;
use Illuminate\Support\Facades\DB;
class StockMinimoController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $stockQuery = StockMinimo::with('modelo');

        if (!empty($search)) {
            $search = mb_strtolower($search);

            $stockQuery->whereHas('modelo', function ($query) use ($search) {
                $query->where(DB::raw('LOWER(modelo)'), 'like', "%{$search}%")
                    ->orWhere(DB::raw('LOWER(nombre_modelo)'), 'like', "%{$search}%");
            });
        }

        $stock_minimo = $stockQuery->paginate(10)->withQueryString();

        return inertia('configuracion/StockMinimo', [
            'stock_minimo' => $stock_minimo,
        ]);
    }

    public function update_stock_minimo(Request $request){
        $request->validate([
            'id' => 'required',
            'stock_minimo' => 'required|integer|min:0',
        ]);

        $stock = StockMinimo::where('id', $request->id)->first();

        if(!$stock){
            return back()->withErrors([
                'message' => 'Ocurrio un error.'
            ]);
        }

        $stock->update([
            'stock_minimo' => $request->stock_minimo,
        ]);
    }
}
