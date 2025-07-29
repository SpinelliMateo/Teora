<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        $permisos = Permission::all();
        return inertia('configuracion/Roles', [
            'roles' => $roles,
            'permisos' => $permisos,
        ]);
    }
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:roles,name',
                'permissions' => 'array',
            ], [
                'name.required' => 'El nombre es obligatorio.',
                'name.string' => 'El nombre debe ser texto.',
                'name.max' => 'El nombre no puede superar los 255 caracteres.',
                'name.unique' => 'El nombre de rol ya existe.',
            ]);

            $role = Role::create(['name' => $validated['name']]);
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }
            return redirect()->route('roles')->with('success', 'Rol creado correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors([
                'error' => 'Por favor, revisa los datos ingresados. ' . collect($e->errors())->flatten()->first()
            ])->withInput();
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error inesperado al crear el rol. Verifica que todos los datos sean correctos e intenta nuevamente.'
            ])->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:roles,name,' . $id,
                'permissions' => 'array',
            ], [
                'name.required' => 'El nombre es obligatorio.',
                'name.string' => 'El nombre debe ser texto.',
                'name.max' => 'El nombre no puede superar los 255 caracteres.',
                'name.unique' => 'El nombre de rol ya existe.',
            ]);

            $role = Role::findOrFail($id);
            $role->name = $validated['name'];
            $role->save();
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }
            return redirect()->route('roles')->with('success', 'Rol actualizado correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors([
                'error' => 'Por favor, revisa los datos ingresados. ' . collect($e->errors())->flatten()->first()
            ])->withInput();
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error inesperado al actualizar el rol. Verifica que todos los datos sean correctos e intenta nuevamente.'
            ])->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $role = Role::findOrFail($id);
            $role->delete();
            return back()->with('success', 'Rol eliminado correctamente');
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error al eliminar el rol: ' . $e->getMessage()
            ]);
        }
    }
}
