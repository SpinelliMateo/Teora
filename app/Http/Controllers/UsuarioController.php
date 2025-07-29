<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $usuarios = User::with('roles');
        if ($search) {
            $usuarios->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(name) LIKE ?', ["%" . strtolower($search) . "%"])
                    ->orWhereRaw('LOWER(apellido) LIKE ?', ["%" . strtolower($search) . "%"])
                    ->orWhereHas('roles', function ($q) use ($search) {
                        $q->whereRaw('LOWER(name) LIKE ?', ["%" . strtolower($search) . "%"]);
                    });
            });
        }
        $roles = Role::all();
        return inertia('configuracion/Usuarios', [
            'usuarios' => $usuarios->get(),
            'roles' => $roles
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'usuario_name' => 'required|string|max:255',
                'usuario_apellido' => 'nullable|string|max:255',
                'usuario_username' => 'required|string|max:255|unique:users,username',
                'usuario_email' => 'required|email|max:255|unique:users,email',
                'usuario_password' => 'required|string|min:6|confirmed',
                'usuario_rol' => 'required|exists:roles,id',
            ], [
                'usuario_name.required' => 'El nombre es obligatorio.',
                'usuario_username.required' => 'El nombre de usuario es obligatorio.',
                'usuario_username.unique' => 'El nombre de usuario ya existe.',
                'usuario_email.required' => 'El email es obligatorio.',
                'usuario_email.unique' => 'El email ya existe.',
                'usuario_password.required' => 'La contraseña es obligatoria.',
                'usuario_password.confirmed' => 'Las contraseñas no coinciden.',
                'usuario_rol.required' => 'Debe seleccionar un rol.',
            ]);

            $user = User::create([
                'name' => $validated['usuario_name'],
                'apellido' => $validated['usuario_apellido'] ?? '',
                'email' => $validated['usuario_email'],
                'password' => bcrypt($validated['usuario_password']),
                'username' => $validated['usuario_username'],
            ]);

            $role = Role::find($validated['usuario_rol']);
            if ($role) {
                $user->assignRole($role->name);
            }

            return redirect()->route('usuarios')->with('success', 'Usuario creado correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors([
                'error' => 'Por favor, revisa los datos ingresados. ' . collect($e->errors())->flatten()->first()
            ])->withInput();
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error inesperado al crear el usuario. Verifica que todos los datos sean correctos e intenta nuevamente.'
            ])->withInput();
        }
    }
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();
            return back()->with('success', 'Usuario eliminado correctamente');
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error al eliminar el usuario: ' . $e->getMessage()
            ]);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'usuario_name' => 'required|string|max:255',
                'usuario_apellido' => 'nullable|string|max:255',
                'usuario_username' => 'required|string|max:255|unique:users,username,' . $id,
                'usuario_email' => 'required|email|max:255|unique:users,email,' . $id,
                'usuario_password' => 'nullable|string|min:6|confirmed',
                'usuario_rol' => 'required|exists:roles,id',
            ], [
                'usuario_name.required' => 'El nombre es obligatorio.',
                'usuario_name.string' => 'El nombre debe ser texto.',
                'usuario_name.max' => 'El nombre no puede superar los 255 caracteres.',
                'usuario_apellido.string' => 'El apellido debe ser texto.',
                'usuario_apellido.max' => 'El apellido no puede superar los 255 caracteres.',
                'usuario_username.required' => 'El nombre de usuario es obligatorio.',
                'usuario_username.string' => 'El nombre de usuario debe ser texto.',
                'usuario_username.max' => 'El nombre de usuario no puede superar los 255 caracteres.',
                'usuario_username.unique' => 'El nombre de usuario ya existe.',
                'usuario_email.required' => 'El email es obligatorio.',
                'usuario_email.email' => 'El email debe ser válido.',
                'usuario_email.max' => 'El email no puede superar los 255 caracteres.',
                'usuario_email.unique' => 'El email ya existe.',
                'usuario_password.string' => 'La contraseña debe ser texto.',
                'usuario_password.min' => 'La contraseña debe tener al menos 6 caracteres.',
                'usuario_password.confirmed' => 'Las contraseñas no coinciden.',
                'usuario_rol.required' => 'Debe seleccionar un rol.',
                'usuario_rol.exists' => 'El rol seleccionado no existe.',
            ]);

            $user = User::findOrFail($id);
            $user->name = $validated['usuario_name'];
            $user->apellido = $validated['usuario_apellido'] ?? '';
            $user->username = $validated['usuario_username'];
            $user->email = $validated['usuario_email'];
            if (!empty($validated['usuario_password'])) {
                $user->password = bcrypt($validated['usuario_password']);
            }
            $user->save();

            $role = Role::find($validated['usuario_rol']);
            if ($role) {
                $user->syncRoles([$role->name]);
            }

            return redirect()->route('usuarios')->with('success', 'Usuario actualizado correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors([
                'error' => 'Por favor, revisa los datos ingresados. ' . collect($e->errors())->flatten()->first()
            ])->withInput();
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error inesperado al actualizar el usuario. Verifica que todos los datos sean correctos e intenta nuevamente.'
            ])->withInput();
        }
    }
}
