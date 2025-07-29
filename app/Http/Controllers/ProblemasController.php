<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Problema;
use App\Models\SubProblema;
use Illuminate\Support\Facades\Log;

class ProblemasController extends Controller
{
    public function index(){
        $problemas = Problema::orderBy('created_at' ,'asc')->with('subproblemas')->get();

        return inertia('configuracion/Problemas', [
            'problemas' => $problemas,
        ]);
    }

    public function create_problema(Request $request){
        $validate = $request->validate([
            'problema' => 'required|string',
            'subproblemas' => 'required|array|min:1',
            'subproblemas.*' => 'required|string|min:1',
        ]);

        try{
            $problema = Problema::create([
                'nombre' => $request->problema,
            ]);

            foreach($request->subproblemas as $subproblema){
                SubProblema::create([
                    'problema_id' => $problema->id,
                    'nombre' => $subproblema,
                ]);
            }
        }catch (\Exception $e){
            Log::error($e->getMessage());
            return back()->withErrors([
                'message' => 'Ocurrio un error.'
            ]);
        }
    }

    public function update_problema(Request $request){
        $validate = $request->validate([
            'id' => 'required',
            'problema' => 'required|string',
            'subproblemas' => 'required|array|min:1',
            'subproblemas.*' => 'required|string|min:1',
        ]);

        try{
            
            $problema = Problema::findOrFail($request->id);
            $problema->update([
                'nombre' => $request->problema,
            ]);

            $problema->subproblemas()->delete();

            foreach ($request->subproblemas as $sub) {
                SubProblema::create([
                    'problema_id' => $problema->id,
                    'nombre' => $sub,
                ]);
            }
            
        }catch (\Exception $e){
            Log::error($e->getMessage());
            return back()->withErrors([
                'message' => 'Ocurrio un error.'
            ]);
        }
    }

    public function get_subproblemas_by_id(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);
        
        try{
            $problema = Problema::findOrfail($request->id);

            return response()->json([
                'subproblemas' => $problema->subproblemas, 
            ]); 
        }catch (\Exception $e){
            Log::error($e->getMessage());
            return response()->json([
                'message' => 'Ocurrio un error.'
            ], 500);
        }
    }

    public function delete_problema(Request $request){
        $validate = $request->validate([
            'id' => 'required',
        ]);

        try{
            $problema = Problema::findOrFail($request->id);

            $problema->delete();
            
        }catch (\Exception $e){
            Log::error($e->getMessage());
            return back()->withErrors([
                'message' => 'Ocurrio un error.'
            ]);
        }
    }
}
